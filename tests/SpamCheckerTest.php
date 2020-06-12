<?php

namespace App\Tests;

use App\Entity\Comment;
use App\SpamChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class SpamCheckerTest extends TestCase
{
    /**
     * @test
     */
    public function testSpamScoreWithInvalidRequest()
    {
        $comment = new Comment();
        $comment->setCreatedAtValue();
        $context = [];

        $client = new MockHttpClient([new MockResponse('false', [
            'response_headers' => ['x-akismet-debug-help: Invalid key']
        ])]);
        $checker = new SpamChecker($client, 'abcde');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to check for spam: false
(Invalid key).');
        $checker->getSpamScore($comment, $context);
    }
}
