<?php

namespace PrettyHelpfulPHP;

use PHP_CodeSniffer_File;
use PHP_CodeSniffer_Sniff;

abstract class AbstractRestrictedCallSniff implements PHP_CodeSniffer_Sniff
{
    /**
     * @var boolean
     */
    public $error = true;

    /**
     * @var array
     */
    public $forbidden;

    /**
     * @var array
     */
    private $forbiddenNames;

    /**
     * @return array
     */
    final public function register() : array
    {
        $forbidden = $this->forbidden ?? $this->getDefaultForbidden();
        $forbiddenNames = array_map('strtolower', array_keys($forbidden));
        $forbiddenValues = array_map(function ($config) {
            if (isset($config['allowedContext'])) {
                $config['allowedContext'] = array_map('strtolower', $config['allowedContext']);
            }
            return $config;
        }, array_values($forbidden));

        $this->forbiddenNames = $forbiddenNames;
        $this->forbidden = array_combine($forbiddenNames, $forbiddenValues);

        return $this->getTokensToWatch();
    }

    /**
     * @param PHP_CodeSniffer_File $file     The file where the token was found.
     * @param int|bool             $position The position in the stack where the token was found.
     *
     * @return int|void
     */
    final public function process(PHP_CodeSniffer_File $file, $position)
    {
        list($position, $name) = $this->getForbiddenCall($file, $position);
        if ($position !== false) {
            $this->addError($file, $position, $name);
        }
    }

    /**
     * @return array
     */
    abstract protected function getDefaultForbidden() : array;

    /**
     * @return array
     */
    abstract protected function getTokensToWatch() : array;

    /**
     * @param PHP_CodeSniffer_File $file
     * @param int                  $position
     * @return array
     */
    abstract protected function getForbiddenCall(PHP_CodeSniffer_File $file, int $position) : array;

    /**
     * @param PHP_CodeSniffer_File $file
     * @param int                  $position
     * @param string $name
     */
    final protected function addError(PHP_CodeSniffer_File $file, int $position, string $name)
    {
        $config = $this->getConfig($name);
        $allowedContext = $config['allowedContext'] ?? null;
        $minimumArguments = $config['minimumArguments'] ?? 0;

        $message = sprintf(
            'The use of %s%s is %s%s.',
            $name,
            $minimumArguments > 0 ? " with less than {$minimumArguments} argument(s)" : '',
            $this->error ? 'forbidden' : 'discouraged',
            $allowedContext === null ? '' : ' outside of ' . implode(', ', $allowedContext) . ' functions'
        );

        if ($this->error === false) {
            $file->addWarning($message, $position, 'Discouraged');
            return;
        }

        $file->addError($message, $position, 'Found');
    }

    /**
     * @param string $name
     * @return array
     */
    final protected function getConfig(string $name) : array
    {
        return $this->forbidden[strtolower($name)] ?? [];
    }

    /**
     * @param PHP_CodeSniffer_File $file
     * @param int                  $position
     * @return array
     */
    final protected function getFullNamespaceAndPosition(PHP_CodeSniffer_File $file, int $position) : array
    {
        $start = $this->getFirstInstanceOfTokenInSequence($file, $position, [T_STRING, T_NS_SEPARATOR]);
        list($namespace, $start, $end) = $this->getFullNamespace($file, $start);

        if ($namespace !== '' && $namespace[0] === '\\') {
            $namespace = substr($namespace, 1);
        }

        return [$namespace, $start, $end];
    }

    final private function getFirstInstanceOfTokenInSequence(
        PHP_CodeSniffer_File $file,
        int $position,
        array $types
    ) : int {
        $position = $file->findPrevious($types + [T_WHITESPACE], $position, null, true);
        return $file->findNext($types, $position);
    }

    /**
     * @param PHP_CodeSniffer_File $file
     * @param int                  $position
     * @param array|null           $ignore
     * @return array
     */
    final protected function getNextToken(PHP_CodeSniffer_File $file, int $position, array $ignore = null) : array
    {
        $position = $this->getNextTokenPosition($file, $position, $ignore);

        return [
            $this->getToken($file, $position),
            $position
        ];
    }

    /**
     * @param PHP_CodeSniffer_File $file
     * @param int                  $position
     * @param array|null           $ignore
     * @return bool|int
     */
    final protected function getNextTokenPosition(PHP_CodeSniffer_File $file, int $position, array $ignore = null)
    {
        return $file->findNext($ignore ?? [T_WHITESPACE], $position + 1, null, true);
    }

    /**
     * @param PHP_CodeSniffer_File $file
     * @param int                  $position
     * @return array
     */
    final protected function getNextTokenSkipParenthesisAndBrackets(PHP_CodeSniffer_File $file, int $position) : array
    {
        list($token, $position) = $this->getNextToken($file, $position);
        if ($position === false) {
            return [$token, $position];
        }

        $closer = $token['parenthesis_closer'] ?? $token['bracket_closer'] ?? null;
        if ($closer !== null) {
            return $this->getNextToken($file, $closer);
        }

        return [$token, $position];
    }

    /**
     * @param PHP_CodeSniffer_File $file
     * @param int                  $position
     * @param array|null           $ignore
     * @return array
     */
    final protected function getPreviousToken(PHP_CodeSniffer_File $file, int $position, array $ignore = null) : array
    {
        $position = $this->getPreviousTokenPosition($file, $position, $ignore);

        return [
            $this->getToken($file, $position),
            $position,
        ];
    }

    /**
     * @param PHP_CodeSniffer_File $file
     * @param int                  $position
     * @param array|null           $ignore
     * @return bool|int
     */
    final protected function getPreviousTokenPosition(PHP_CodeSniffer_File $file, int $position, array $ignore = null)
    {
        return $file->findPrevious($ignore ?? [T_WHITESPACE], $position - 1, null, true);
    }

    /**
     * @param PHP_CodeSniffer_File $file
     * @param int                  $position
     * @return array|null
     */
    final protected function getToken(PHP_CodeSniffer_File $file, int $position)
    {
        if ($position === false) {
            return null;
        }

        $tokens = $file->getTokens();
        return $tokens[$position];
    }

    /**
     * @param PHP_CodeSniffer_File $file
     * @param int                  $position
     * @param string               $name
     * @return bool
     */
    final protected function isForbiddenAndUsedIncorrectly(
        PHP_CodeSniffer_File $file,
        int $position,
        string $name
    ) : bool {
        if ($this->isNotInForbiddenList($name)) {
            return false;
        }

        $config = $this->getConfig($name);
        if ($this->isBeingUsedCorrectly($file, $position, $config)) {
            return false;
        }

        return true;
    }

    /**
     * @param PHP_CodeSniffer_File $file
     * @param int                  $position
     * @return bool
     */
    final protected function isNotBeingInvoked(PHP_CodeSniffer_File $file, int $position) : bool
    {
        $currentToken = $this->getToken($file, $position);
        if ($currentToken['code'] !== T_STRING) {
            return true;
        }

        list($nextToken) = $this->getNextToken($file, $position);
        if ($nextToken['code'] !== T_OPEN_PARENTHESIS) {
            return true;
        }

        return false;
    }

    /**
     * @param PHP_CodeSniffer_File $file
     * @param int                  $position
     * @return string
     */
    private function getEnclosingFunctionName(PHP_CodeSniffer_File $file, int $position) : string
    {
        $enclosingFunctionPosition = $file->findPrevious(T_FUNCTION, $position);
        if ($enclosingFunctionPosition === false) {
            return null;
        }

        return $file->getDeclarationName($enclosingFunctionPosition);
    }

    /**
     * @param PHP_CodeSniffer_File $file
     * @param int                  $position
     * @return int
     */
    private function getArgumentCount(PHP_CodeSniffer_File $file, int $position) : int
    {
        list($token) = $this->getNextToken($file, $position);
        $position = $token['parenthesis_opener'];
        $closingParenthesisPosition = $token['parenthesis_closer'];

        list($token) = $this->getNextToken($file, $position);
        if ($token['code'] === T_CLOSE_PARENTHESIS) {
            return 0;
        }

        return 1 + $this->getFollowingArgumentCount($file, $position, $closingParenthesisPosition);
    }

    /**
     * @param PHP_CodeSniffer_File $file
     * @param int                  $position
     * @param int                  $end
     * @return int
     */
    private function getFollowingArgumentCount(PHP_CodeSniffer_File $file, int $position, int $end) : int
    {
        $argumentCount = 0;
        while ($position < $end) {
            list($token, $position) = $this->getNextTokenSkipParenthesisAndBrackets($file, $position);

            if ($token['code'] === T_COMMA) {
                $argumentCount += 1;
            }
        }

        return $argumentCount;
    }

    /**
     * @param PHP_CodeSniffer_File $file
     * @param int                  $position
     * @return array
     */
    private function getFullNamespace(PHP_CodeSniffer_File $file, int $position) : array
    {
        $start = $position;
        $end = $position;

        $position = $this->getPreviousTokenPosition($file, $position);
        $namespacePieces = [];
        while ($position !== false) {
            $end = $position;
            list($token, $position) = $this->getNextToken($file, $position);
            $code = $token['code'];
            if ($code !== T_STRING && $code !== T_NS_SEPARATOR) {
                break;
            }

            $namespacePieces[] = $token['content'];
        }

        return [
            implode('', $namespacePieces),
            $start,
            $end
        ];
    }

    /**
     * @param PHP_CodeSniffer_File $file
     * @param int                  $position
     * @param array                $config
     * @return bool
     */
    private function isBeingUsedCorrectly(PHP_CodeSniffer_File $file, int $position, array $config) : bool
    {
        $acceptedContext = $config['allowedContext'] ?? [];
        if ($this->isInAllowedContext($file, $position, $acceptedContext)) {
            return true;
        }

        $minimumArguments = $config['minimumArguments'] ?? -1;
        if ($this->isUsingEnoughArguments($file, $position, $minimumArguments)) {
            return true;
        }

        return false;
    }

    /**
     * @param PHP_CodeSniffer_File $file
     * @param int                  $position
     * @param array                $acceptedContext
     * @return bool
     */
    private function isInAllowedContext(PHP_CodeSniffer_File $file, int $position, array $acceptedContext) : bool
    {
        if (count($acceptedContext) === 0) {
            return false;
        }

        $enclosingFunctionName = $this->getEnclosingFunctionName($file, $position);
        if ($enclosingFunctionName === null) {
            return false;
        }

        return in_array(strtolower($enclosingFunctionName), $acceptedContext, true);
    }

    /**
     * @param string $name
     * @return bool
     */
    private function isNotInForbiddenList(string $name) : bool
    {
        return !in_array(strtolower($name), $this->forbiddenNames, true);
    }

    /**
     * @param PHP_CodeSniffer_File $file
     * @param int                  $position
     * @param int                  $minimumArguments
     * @return bool
     */
    private function isUsingEnoughArguments(PHP_CodeSniffer_File $file, int $position, int $minimumArguments) : bool
    {
        if ($minimumArguments < 0) {
            return false;
        }

        $argumentCount = $this->getArgumentCount($file, $position);
        if ($argumentCount >= $minimumArguments) {
            return true;
        }

        return false;
    }
}
