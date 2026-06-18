<?php
/**
 * PHPStan stubs for the WordPress 7.0+ Core AI Client.
 *
 * These symbols ship in WordPress 7.0 but are absent from wordpress-stubs 6.8.
 * This file is consumed only by PHPStan (via stubFiles in phpstan.neon) and
 * is never loaded at runtime.
 *
 * @package Blockophon
 */

/**
 * Returns an AI prompt builder for text, image, and other generation tasks.
 *
 * Available in WordPress 7.0+. Guard call sites with function_exists().
 *
 * @param string $prompt Optional initial prompt text.
 * @return WP_AI_Client_Prompt_Builder
 */
function wp_ai_client_prompt( string $prompt = '' ): WP_AI_Client_Prompt_Builder {}

/**
 * Fluent builder for WordPress Core AI Client prompts.
 */
class WP_AI_Client_Prompt_Builder {

	/**
	 * Sets the system instruction for the AI model.
	 *
	 * @param string $instruction The system instruction.
	 * @return static
	 */
	public function using_system_instruction( string $instruction ): static {}

	/**
	 * Sets the sampling temperature.
	 *
	 * @param float $temperature Value between 0.0 and 2.0.
	 * @return static
	 */
	public function using_temperature( float $temperature ): static {}

	/**
	 * Whether a configured provider supports text generation.
	 * Performs no API call.
	 *
	 * @return bool
	 */
	public function is_supported_for_text_generation(): bool {}

	/**
	 * Generates text from the configured prompt.
	 *
	 * @return string|\WP_Error Generated text, or WP_Error on failure.
	 */
	public function generate_text() {}
}
