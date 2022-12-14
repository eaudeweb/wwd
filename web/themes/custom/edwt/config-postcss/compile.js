const chalk = require('chalk');
const log = require('./log');
const fs = require('fs');
const postcssMixins = require('postcss-mixins');
const postcss = require('postcss');
const postcssCalc = require("postcss-calc");
const postcssDropUnusedVars = require("postcss-dropunusedvars");
const postcssImport = require('postcss-import');
const postcssUrl = require('postcss-url');
const postcssPresetEnv = require('postcss-preset-env');
// cspell:ignore pxtorem
const postcssPixelsToRem = require('postcss-pxtorem');

module.exports = (filePath, callback) => {
  // Transform the file.
  fs.readFile(filePath, (err, css) => {
    postcss([
      postcssImport({
       plugins: [
         // On import, remove the comments from variables.pcss.css so they don't
         // appear as useless comments at the top files that import these
         // variables.
         postcss.plugin('remove-unwanted-comments-from-variables', (options) => {
           return css => {
            console.log('aaa', css.source.input.file);
             if (css.source.input.file.indexOf('variables.pcss.css') !== -1) {
               css.walk(node => {
                 if (node.type === 'comment') {
                   node.remove();
                 }
               });
             }
           };
         }),
       ],
      }),
      postcssMixins,
      // postcssDropUnusedVars,
      postcssPresetEnv({
        stage: 1,
        preserve: false,
        autoprefixer: {
          cascade: false,
          grid: 'no-autoplace',
        },
        features: {
          'custom-properties': false,
          'blank-pseudo-class': false,
          'focus-visible-pseudo-class': false,
          'focus-within-pseudo-class': false,
          'has-pseudo-class': false,
          'image-set-function': false,
          'prefers-color-scheme-query': false,
        }
      }),
      postcssCalc,
      postcssPixelsToRem({
          propList: [
            '*',
            '!background-position',
            '!border',
            '!border-width',
            '!box-shadow',
            '!border-top*',
            '!border-right*',
            '!border-bottom*',
            '!border-left*',
            '!border-start*',
            '!border-end*',
            '!outline*',
          ],
          mediaQuery: true,
          minPixelValue: 3,
      }),
      // postcssHeader({
      //   header: `/*\n * DO NOT EDIT THIS FILE.\n * JoTheme\n*/`,
      // }),
      postcssUrl({
        filter: '**/*.svg',
        url: 'inline',
        optimizeSvgEncode: true,
      })
    ])
    .process(css, { from: filePath })
    .then(result => {
      callback(result.css);
    })
    .catch(error => {
      log(chalk.red(error));
      process.exitCode = 1;
    });
  });
};
