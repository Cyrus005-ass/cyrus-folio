import { build } from 'esbuild';
import { readdir } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const scriptDir = path.dirname(fileURLToPath(import.meta.url));
const rootDir = path.resolve(scriptDir, '..');

const targets = [
  { type: 'js', dir: path.join(rootDir, 'public', 'assets', 'js') },
  { type: 'css', dir: path.join(rootDir, 'public', 'assets', 'css') },
];

for (const target of targets) {
  const entries = await readdir(target.dir, { withFileTypes: true });
  for (const entry of entries) {
    if (!entry.isFile()) {
      continue;
    }

    const extension = path.extname(entry.name).toLowerCase();
    if (extension !== `.${target.type}` || entry.name.includes('.min.')) {
      continue;
    }

    const inputFile = path.join(target.dir, entry.name);
    const outputFile = path.join(target.dir, entry.name.replace(extension, `.min${extension}`));

    await build({
      entryPoints: [inputFile],
      outfile: outputFile,
      bundle: false,
      minify: true,
      charset: 'ascii',
      legalComments: 'none',
      sourcemap: false,
      target: target.type === 'js' ? ['es2019'] : undefined,
      logLevel: 'silent',
    });

    console.log(`built ${path.relative(rootDir, outputFile)}`);
  }
}