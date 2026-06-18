import { store, getContext } from '@wordpress/interactivity';

store( 'blockophon/blockophon', {
	actions: {
		toggleColorValue() {
			const context = getContext();
			context.active = ! context.active;
		},
	},
} );
