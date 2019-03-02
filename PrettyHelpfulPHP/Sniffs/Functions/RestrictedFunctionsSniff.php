<?php

namespace PrettyHelpfulPHP\Sniffs\Functions;

use PHP_CodeSniffer_File;
use PrettyHelpfulPHP\AbstractRestrictedCallSniff;

final class RestrictedFunctionsSniff extends AbstractRestrictedCallSniff
{
    /**
     * @var array
     */
    const DEFAULT_ALLOWED_CONTEXT = [
        '__construct',
        'init',
        'setUp',
    ];

    /**
     * @var array
     */
    const DEFAULT_FORBIDDEN_FUNCTIONS = [
        'date' => [
            'allowedContext' => self::DEFAULT_ALLOWED_CONTEXT,
            'minimumArguments' => 2,
        ],
        'date_create' => [
            'allowedContext' => self::DEFAULT_ALLOWED_CONTEXT,
            'minimumArguments' => 1
        ],
        'date_create_immutable' => [
            'allowedContext' => self::DEFAULT_ALLOWED_CONTEXT,
            'minimumArguments' => 1
        ],
        'get_date' => [
            'allowedContext' => self::DEFAULT_ALLOWED_CONTEXT,
            'minimumArguments' => 1
        ],
        'gettimeofday' => [
            'allowedContext' => self::DEFAULT_ALLOWED_CONTEXT,
        ],
        'gmdate' => [
            'allowedContext' => self::DEFAULT_ALLOWED_CONTEXT,
            'minimumArguments' => 2
        ],
        'gmmktime' => [
            'allowedContext' => self::DEFAULT_ALLOWED_CONTEXT,
            'minimumArguments' => 6
        ],
        'gmstrftime' => [
            'allowedContext' => self::DEFAULT_ALLOWED_CONTEXT,
            'minimumArguments' => 2
        ],
        'idate' => [
            'allowedContext' => self::DEFAULT_ALLOWED_CONTEXT,
            'minimumArguments' => 2
        ],
        'localtime' => [
            'allowedContext' => self::DEFAULT_ALLOWED_CONTEXT,
            'minimumArguments' => 2
        ],
        'mktime' => [
            'allowedContext' => self::DEFAULT_ALLOWED_CONTEXT,
            'minimumArguments' => 6
        ],
        'strftime' => [
            'allowedContext' => self::DEFAULT_ALLOWED_CONTEXT,
            'minimumArguments' => 2
        ],
        'microtime' => [
            'allowedContext' => self::DEFAULT_ALLOWED_CONTEXT,
        ],
        'strtotime' => [
            'allowedContext' => self::DEFAULT_ALLOWED_CONTEXT,
            'minimumArguments' => 2
        ],
        'time' => [
            'allowedContext' => self::DEFAULT_ALLOWED_CONTEXT,
        ],
    ];

    /**
     * @var array
     */
    const NOT_A_FUNCTION_INDICATOR_TOKENS = [
        T_AS,
        T_CONST,
        T_DOUBLE_COLON,
        T_FUNCTION,
        T_IMPLEMENTS,
        T_INSTEADOF,
        T_NEW,
        T_NS_SEPARATOR,
        T_OBJECT_OPERATOR,
        T_PRIVATE,
        T_PROTECTED,
        T_PUBLIC,
    ];

    /**
     * @return array
     */
    protected function getDefaultForbidden() : array
    {
        return self::DEFAULT_FORBIDDEN_FUNCTIONS;
    }

    /**
     * @return array
     */
    protected function getTokensToWatch() : array
    {
        return [T_STRING];
    }

    /**
     * @param PHP_CodeSniffer_File $file
     * @param int                  $position
     * @return array
     */
    protected function getForbiddenCall(PHP_CodeSniffer_File $file, int $position) : array
    {
        list($functionName, $start, $end) = $this->getFullNamespaceAndPosition($file, $position);

        if ($this->isNotAFunctionCall($file, $start, $end)) {
            return [false, null];
        }

        if ($this->isForbiddenAndUsedIncorrectly($file, $position, $functionName)) {
            return [$position, $functionName];
        }

        return [false, null];
    }

    /**
     * @param PHP_CodeSniffer_File $file
     * @param int                  $start
     * @param int                  $end
     * @return bool
     */
    private function isNotAFunctionCall(PHP_CodeSniffer_File $file, int $start, int $end) : bool
    {
        list($previousToken) = $this->getPreviousToken($file, $start);
        if ($this->isNotAFunctionCallPrefix($previousToken)) {
            return true;
        }

        if ($this->isNotBeingInvoked($file, $end)) {
            return true;
        }

        return false;
    }

    private function isNotAFunctionCallPrefix($token) : bool
    {
        return in_array($token['code'], self::NOT_A_FUNCTION_INDICATOR_TOKENS);
    }
}
