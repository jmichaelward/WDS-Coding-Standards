<?php // @codingStandardsIgnoreLine: Class filename is ok.
/**
 * Parses and verifies the doc comments for classes.
 *
 * @category Commands
 * @since 1.1.0
 *
 * @package  PHP_CodeSniffer
 */

/**
 * Parses and verifies the doc comments for classes.
 *
 * @since    1.0.0
 * @category Commands
 * @package  PHP_CodeSniffer
 */
class WebDevStudios_Sniffs_Commenting_ClassCommentSniff extends WebDevStudios_Sniffs_Commenting_FileCommentSniff {

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @author Jason Witt
	 * @since  1.1.0
	 *
	 * @return array
	 */
	public function register() {
		return array(
			T_CLASS,
			T_INTERFACE,
		);
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param PHP_CodeSniffer_File $phpcs_file The file being scanned.
	 * @param int                  $stack_ptr  The position of the current token
	 *                                         in the stack passed in $tokens.
	 *
	 * @author Jason Witt, Aubrey Portwood
	 * @since  1.1.0
	 */
	public function process( PHP_CodeSniffer_File $phpcs_file, $stack_ptr ) {
		$tokens = $phpcs_file->getTokens();
		$find = PHP_CodeSniffer_Tokens::$methodPrefixes; // @codingStandardsIgnoreStart
		$find[] = T_WHITESPACE;
		$comment_end = $phpcs_file->findPrevious( $find, ( $stack_ptr - 1 ), null, true );

		// Class docblocks live by the same rules as the file docblock.
		$this->processTags( $phpcs_file, $stack_ptr, $tokens[ $comment_end ]['comment_opener'] );
	}
}
