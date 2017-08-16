<?php // @codingStandardsIgnoreLine: Class filename is ok.
/**
 * Variable specific rules:
 *
 * An @since tag is required on all variable docblocks.
 * An @author tag is required on all variable docblocks.
 *
 * @category Commands
 * @since 1.1.0
 *
 * @package  PHP_CodeSniffer
 */

/**
 * Parses and verifies the doc comments for variables.
 *
 * @since    1.0.0
 * @category Commands
 * @package  PHP_CodeSniffer
 */
class WebDevStudios_Sniffs_Commenting_VariableCommentSniff extends WebDevStudios_Sniffs_Commenting_FileCommentSniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @since  1.1.0
	 * @author Jason Witt
	 *
	 * @return array
	 */
	public function register() {
		return array(
			T_PUBLIC,
			T_PRIVATE,
			T_PROTECTED,
			T_VAR,
			T_STATIC,
		);
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param PHP_CodeSniffer_File $phpcs_file The file being scanned.
	 * @param int                  $stack_ptr  The position of the current token
	 *                                         in the stack passed in $tokens.
	 *
	 * @since  1.1.0
	 * @author Jason Witt, Aubrey Portwood
	 */
	public function process( PHP_CodeSniffer_File $phpcs_file, $stack_ptr ) {

		// Variable comments require @author.
		$this->tags = array_merge( $this->tags, array( '@author' => true ) );

		$tokens = $phpcs_file->getTokens();
		$find = PHP_CodeSniffer_Tokens::$methodPrefixes; // @codingStandardsIgnoreStart
		$find[] = T_WHITESPACE;
		$comment_end = $phpcs_file->findPrevious( $find, ( $stack_ptr - 1 ), null, true );

		if ( isset( $tokens[ $comment_end ]['comment_opener'] ) ) {

			// Class docblocks live by the same rules as the file docblock.
			$this->processTags( $phpcs_file, $stack_ptr, $tokens[ $comment_end ]['comment_opener'] );
		}
	}
}
