<?php
/**
 * Opulence
 *
 * @link      https://www.opulencephp.com
 * @copyright Copyright (C) 2016 David Young
 * @license   https://github.com/opulencephp/Opulence/blob/master/LICENSE.md
 */
namespace Opulence\Tests\Debug\Exceptions\Handlers\Http\Mocks;

use Exception;
use Opulence\Debug\Exceptions\Handlers\Http\ExceptionRenderer as BaseRenderer;

/**
 * Mocks the HTTP exception renderer for use in testing
 */
class ExceptionRenderer extends BaseRenderer
{
    /** @var string The request format */
    private $requestFormat = "html";

    /**
     * Sets the request format (useful for testing
     *
     * @param string $requestFormat The format to use
     */
    public function setRequestFormat(string $requestFormat)
    {
        $this->requestFormat = $requestFormat;
    }

    /**
     * @inheritdoc
     */
    protected function getDevelopmentEnvironmentContent(Exception $ex, int $statusCode) : string
    {
        return $ex->getMessage();
    }

    /**
     * @inheritdoc
     */
    protected function getProductionEnvironmentContent(Exception $ex, int $statusCode) : string
    {
        return "Something went wrong";
    }

    /**
     * @inheritdoc
     */
    protected function getRequestFormat() : string
    {
        return $this->requestFormat;
    }
}