module.exports = ctx => ({
  map: !ctx.env || ctx.env !== 'production' ? { inline: false } : false,
  plugins: [
    require('postcss-custom-properties')({
      preserve: false,
    }),
    require("postcss-calc"),
    require('autoprefixer')({
      cascade: false
    }),
  ]
});
