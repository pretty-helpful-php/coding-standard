<?php

namespace PrettyHelpfulPHP\Sniffs\Classes;

use DateTime;
use DateTimeImmutable;
use PHP_CodeSniffer_File;
use PrettyHelpfulPHP\AbstractRestrictedCallSniff;

final class RestrictedClassInstancesSniff extends AbstractRestrictedCallSniff
{
    /**
     * @var array
     */
    const DEFAULT_ALLOWED_CONTEXT = [
        '__construct',
        'init',
        'setup',
    ];

    /**
     * @var array
     */
    const DEFAULT_FORBIDDEN_CLASSES = [
        DateTime::class => [
            'allowedContext' => self::DEFAULT_ALLOWED_CONTEXT,
        ],
        DateTimeImmutable::class => [
            'allowedContext' => self::DEFAULT_ALLOWED_CONTEXT,
        ],
    ];

    /**
     * @return array
     */
    protected function getDefaultForbidden() : array
    {
        return self::DEFAULT_FORBIDDEN_CLASSES;
    }

    /**
     * @return array
     */
    protected function getTokensToWatch() : array
    {
        return [T_NEW];
    }

    /**
     * @param PHP_CodeSniffer_File $file
     * @param int                  $position
     * @return array
     */
    protected function getForbiddenCall(PHP_CodeSniffer_File $file, int $position) : array
    {
        $position = $this->getNextTokenPosition($file, $position);
        list($className, $start, $end) = $this->getFullNamespaceAndPosition($file, $position);

        if ($this->isNotBeingInvoked($file, $end)) {
            return [false, null];
        }

        if ($this->isForbiddenAndUsedIncorrectly($file, $end, $className)) {
            return [$start, $className];
        }

        return [false, null];
    }
}
