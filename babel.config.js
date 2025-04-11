module.exports = {
  presets: [
    [
      '@babel/preset-env',
      {
        targets: {
          node: 'current',
        },
      },
    ],
    '@babel/preset-react',
  ],
  // This is needed to transform ES modules
  env: {
    test: {
      plugins: [
        '@babel/plugin-transform-modules-commonjs',
      ],
    },
  },
};
