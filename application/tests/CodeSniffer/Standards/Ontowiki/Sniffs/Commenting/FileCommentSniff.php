<?php
/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2012, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * Ontowiki_Sniffs_Commenting_FileCommentSniff.
 *
 * Test for the right file comment in all php files. Seperated check for the copyright Year
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer_Sniff
 * @author    Lars Eidam <lars.eidam@googlemail.com>
 */

/**
 * Ontowiki_Sniffs_Commenting_FileCommentSniff.
 * Check for functions, they are not allowed.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer_Sniff
 * @author    Lars Eidam <lars.eidam@googlemail.com>
 */
class Ontowiki_Sniffs_Commenting_FileCommentSniff implements PHP_CodeSniffer_Sniff
{
    private $_commentStr;
    private $_yearRegEx = "/.*(?<year>[0-9]{4}).*/";
    private $_dateLine = 3;
    private $_dateColumn = 4;
    private $_date;

    public function __construct()
    {
        // this avoid timezone warnings
        date_default_timezone_set('Europe/Berlin');
        $this->_date = date("Y");
        $this->_commentStr = array(
        "/**\n",
        " * This file is part of the {@link http://ontowiki.net OntoWiki} project.\n",
        " *\n",
        " * @copyright Copyright (c) 2006-$this->_date, {@link http://aksw.org AKSW}\n",
        " * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)\n",
        " */"
        );
    }

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_OPEN_TAG);
    }//end register()

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // all line of a DocComment will be checked but if there was fount an error
        // the Loop will break and only this error will be shown, because if there
        // is one error in the DocComment it is quite likely that there are much
        // more errors
        foreach ($this->_commentStr as $commentStrLineNumber => $commentStrLine) {
            $tokenNumber = $stackPtr + $commentStrLineNumber + 1;

            if (!isset($tokens[$tokenNumber])) {
                continue;
            }

            // check if every line direct after the php open tag is a doc comment line
            if ("T_DOC_COMMENT" != $tokens[$tokenNumber]['type']) {
                $error = 'Wrong DocComment: Found "%s" instead of the File Comment.';
                $data = addcslashes($tokens[$tokenNumber]['content'], "\n");
                $phpcsFile->addError($error, $tokenNumber, 'NoFileComment', $data);
                return;
            // check if ever comment line is the same as the defined one in the $commentStr
            } else if ($commentStrLine != $tokens[$tokenNumber]['content']) {
                $commentStrLinePartsDirty = explode(' ', $commentStrLine);
                $commentStrLineParts = array();
                foreach ($commentStrLinePartsDirty as $item) {
                    if (trim($item) !== '') {
                        $commentStrLineParts[] = trim($item);
                    }
                }

                $contentPartsDirty = explode(' ', $tokens[$tokenNumber]['content']);
                $contentParts = array();
                foreach ($contentPartsDirty as $item) {
                    if (trim($item) !== '') {
                        $contentParts[] = trim($item);
                    }
                }

                if (count($commentStrLineParts) !== count($contentParts)) {
                    // add error
                    $data = $tokens[$tokenNumber]['content'];
                    $error = 'Wrong DocComment: Found ' . $data . ' instead of "' . $commentStrLine . '".';
                    $phpcsFile->addError($error, $tokenNumber, 'WrongFileCommentLine');
                    return;
                }

                foreach ($commentStrLineParts as $i => $part) {
                    if ($part !== $contentParts[$i]) {
                        if (($this->_dateLine === $commentStrLineNumber) && ($this->_dateColumn === $i)) {
                            // add warning for wrong date
                            $error = 'Wrong DocComment: Found "%s" instead of "' . $this->_date . '".';
                            $phpcsFile->addWarning(
                                $error,
                                $tokenNumber,
                                'WrongFileCommentYear',
                                trim($contentParts[$i])
                            );
                            continue;
                        } else {
                            // add error
                            $data = $contentParts[$i];
                            $error = 'Wrong DocComment: Found ' . $data . ' instead of "' . $part . '".';
                            $phpcsFile->addError($error, $tokenNumber, 'WrongFileCommentLine');
                            return;
                        }
                    }
                }
            }
        }

        // check if ever line after doc comment is a blank line
        if (isset($tokens[$stackPtr + count($this->_commentStr) + 2])
            && ("T_WHITESPACE" != $tokens[$stackPtr + count($this->_commentStr) + 2]['type'])
        ) {
            $data = addcslashes($tokens[$stackPtr + count($this->_commentStr) + 2]['content'], "\n");
            $error = 'Wrong DocComment: Found "%s" instead of the blank line.';
            $phpcsFile->addError(
                $error,
                $stackPtr + count($this->_commentStr) + 2,
                'NoBlankLineAfterFileComment',
                $data
            );
        }
    }//end process()
}//end class
