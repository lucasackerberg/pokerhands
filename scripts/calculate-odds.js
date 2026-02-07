import { CardGroup, OddsCalculator } from 'poker-odds-calculator';
import fs from 'fs';

function main() {
  try {
    const inputData = fs.readFileSync(0, 'utf8').trim();
    const input = JSON.parse(inputData);
    const { players, board } = input;

    const playerGroups = players.map(p => CardGroup.fromString(p));
    const boardGroup = board ? CardGroup.fromString(board) : undefined;

    const result = OddsCalculator.calculate(playerGroups, boardGroup);
    const equities = result.equities.map(e => ({ equity: e.getEquity() }));

    console.log(JSON.stringify({ equities }));
  } catch (err) {
    console.error(JSON.stringify({ error: err.message }));
    process.exit(1);
  }
}

main();