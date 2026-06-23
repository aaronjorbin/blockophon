const wpPlugin = require( '@wordpress/eslint-plugin' );

module.exports = [
	{
		ignores: [ 'coverage/**', 'build/**', 'vendor/**', 'node_modules/**' ],
	},
	...wpPlugin.configs.recommended,
];
