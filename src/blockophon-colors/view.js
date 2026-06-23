import { store, getContext } from '@wordpress/interactivity';

store( 'blockophon/colors', {
	actions: {
		toggleColorValue() {
			const context = getContext();
			context.active = ! context.active;
		},
	},
} );
