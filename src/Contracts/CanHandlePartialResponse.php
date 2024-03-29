<?php
namespace Cognesy\Instructor\Contracts;

use Cognesy\Instructor\Data\ResponseModel;
use Cognesy\Instructor\Utils\Result;

interface CanHandlePartialResponse
{
    public function handlePartialResponse(string $partialJsonData, ResponseModel $responseModel): void;
}