import fs from 'node:fs';
import vm from 'node:vm';

const html = fs.readFileSync(new URL('../public/index.html', import.meta.url), 'utf8');
const dataMatch = html.match(/window\.SABINO_DATA = (\{.*?\});<\/script>/s);
if (!dataMatch) throw new Error('Could not read Sabino menu data.');

const source = fs.readFileSync(new URL('../assets/js/sabino-static.js', import.meta.url), 'utf8');
const menuStart = source.indexOf('function menuText');
const answerEnd = source.indexOf('function escapeHtml');
if (menuStart < 0 || answerEnd < 0) throw new Error('Could not locate Sabinox answer functions.');

const context = vm.createContext({shop: JSON.parse(dataMatch[1])});
vm.runInContext(`${source.slice(menuStart, answerEnd)}\nthis.answerQuestion = answer;`, context);

const cases = [
  ['How does the order QR work?', ['signed summary', 'not a payment QR']],
  ['I want a milky coffee but my friend wants strong black coffee. What should we get?', ['Latte (₱150)', 'Americano (₱130)', '₱280 total']],
  ['Compare Americano and Latte. Does milk remove the caffeine?', ['coffee-forward', 'does not remove caffeine']],
  ['Is matcha stronger than coffee and which has more caffeine?', ['exact caffeine amounts are not published']],
  ['What drink pairs with Carbonara pasta?', ['Americano (₱130)', '₱400']],
  ['I am pregnant. Which coffee is medically safe for me?', ['healthcare professional', 'exact caffeine']],
  ['I have a dairy allergy. Is a latte safe?', ['cross-contact']],
  ['Why does coffee taste sour and how do I fix extraction?', ['under-extracted']]
];

for (const [question, expectedParts] of cases) {
  const reply = context.answerQuestion(question).content;
  for (const expected of expectedParts) {
    if (!reply.toLowerCase().includes(expected.toLowerCase())) {
      throw new Error(`${question}\nExpected: ${expected}\nReceived: ${reply}`);
    }
  }
}

console.log(`Passed ${cases.length} difficult-question checks.`);
