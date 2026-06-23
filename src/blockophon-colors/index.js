import { registerBlockType } from '@wordpress/blocks';
import './style.scss';
import metadata from './block.json';
import Edit from './edit';

registerBlockType( metadata.name, { edit: Edit } );
