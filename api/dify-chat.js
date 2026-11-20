const DIFY_BASE_URL = 'https://api.dify.ai/v1';

function json(res, status, body) {
  res.statusCode = status;
  res.setHeader('Content-Type', 'application/json; charset=utf-8');
  res.setHeader('Cache-Control', 'no-store');
  res.end(JSON.stringify(body));
}

function cleanRetrievedText(value) {
  return String(value || '')
    .replace(/^#+\s+.*$/gm, '')
    .replace(/^Each entry.*$/gim, '')
    .replace(/:\*\*/g, ':')
    .trim();
}

function isHeadingOnly(value) {
  const text = String(value || '').trim();
  if (/^Question:/i.test(text)) return true;
  return text.length < 90 && !/[.!?]$/.test(text) && !/\bPHP\s*\d+/i.test(text);
}

export default async function handler(req, res) {
  if (req.method !== 'POST') return json(res, 405, { error: 'Method not allowed.' });
  const apiKey = process.env.DIFY_API_KEY;
  const datasetId = process.env.DIFY_DATASET_ID || '45483dff-65bd-45e0-b5c2-79eaa7fe07c5';
  if (!apiKey) return json(res, 503, { error: 'Dify is not configured.' });

  const question = String(req.body?.question || '').trim().slice(0, 1000);
  if (!question) return json(res, 400, { error: 'Please enter a question.' });

  try {
    const response = await fetch(`${DIFY_BASE_URL}/datasets/${datasetId}/retrieve`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${apiKey}`, 'Content-Type': 'application/json' },
      body: JSON.stringify({
        query: question,
        retrieval_model: {
          search_method: 'hybrid_search',
          reranking_enable: false,
          reranking_mode: null,
          reranking_model: { reranking_provider_name: '', reranking_model_name: '' },
          weights: { weight_type: 'customized', keyword_setting: { keyword_weight: 0.3 }, vector_setting: { vector_weight: 0.7, embedding_provider_name: '', embedding_model_name: '' } },
          top_k: 4,
          score_threshold_enabled: true,
          score_threshold: 0.25
        }
      }),
      signal: AbortSignal.timeout(10000)
    });
    if (!response.ok) throw new Error(`Dify returned ${response.status}`);
    const data = await response.json();
    const records = Array.isArray(data.records) ? data.records : [];
    const matches = records
      .map(record => ({ content: cleanRetrievedText(record.segment?.content), score: Number(record.score || 0) }))
      .filter(record => record.content.length > 20 && !isHeadingOnly(record.content))
      .sort((a, b) => b.score - a.score);
    const best = matches[0];
    if (!best) return json(res, 404, { error: 'No matching Dify knowledge was found.' });

    // Dify may split a question heading from its answer. Prefer answer-like
    // segments and combine closely ranked matches for multi-part questions.
    const selected = matches
      .filter(record => record.score >= Math.max(0.25, best.score - 0.12))
      .slice(0, 2);
    return json(res, 200, {
      content: selected.map(record => record.content).join('\n\n'),
      source: 'dify',
      confidence: best.score
    });
  } catch (error) {
    return json(res, 502, { error: 'Dify is temporarily unavailable.' });
  }
}
