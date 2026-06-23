const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const applyOptimization = ( config ) => ( {
	...config,
	optimization: {
		...config.optimization,
		concatenateModules: false,
	},
} );

module.exports = Array.isArray( defaultConfig )
	? defaultConfig.map( applyOptimization )
	: applyOptimization( defaultConfig );
