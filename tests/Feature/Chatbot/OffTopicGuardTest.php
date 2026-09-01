<?php

namespace Tests\Feature\Chatbot;

use App\Http\Controllers\ChatbotController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

/**
 * The assistant is a shopping guide, so requests to write something on the
 * customer's behalf are turned away before they reach the model — that costs
 * nothing and cannot be talked around.
 *
 * Two real defects motivated these: the shop-vocabulary allowance ran first, so
 * "write a poem about shirts" was waved through on the word "shirts"; and shop
 * words were matched as plain substrings, so "cod" matched inside "code" and a
 * coding question counted as a question about cash on delivery.
 */
class OffTopicGuardTest extends TestCase
{
    // The guard itself touches no database, but without this the suite boots
    // against an unmigrated schema and every later test loses its tables.
    use RefreshDatabase;

    private function isOffTopic(string $message): bool
    {
        $class = new ReflectionClass(ChatbotController::class);
        $method = $class->getMethod('isOffTopic');
        $method->setAccessible(true);

        return $method->invoke($class->newInstanceWithoutConstructor(), $message);
    }

    /** @return array<string, array{string}> */
    public static function offTopicRequests(): array
    {
        return [
            'email' => ['write an email to my boss'],
            'draft an email' => ['can you draft an email for me'],
            'leave application' => ['write a leave application'],
            'cover letter' => ['help me write a cover letter'],
            'instagram caption' => ['give me an instagram caption'],
            'code' => ['write python code'],
            'general knowledge' => ['what is the capital of France'],
            'translation' => ['translate this to french'],
            'poem naming a product' => ['write a poem about shirts'],
            'song naming a product' => ['write me a song about your kurtas'],
        ];
    }

    /** @return array<string, array{string}> */
    public static function shopQuestions(): array
    {
        return [
            'size availability' => ['do you have kurtas in size L'],
            'price' => ['what is the price of this shirt'],
            'order status' => ['where is my order'],
            'occasion advice' => ['will this suit a wedding'],
            'returns' => ['can I return this'],
            'cash on delivery' => ['do you accept COD'],
            'category' => ['what tops do you have'],
            'fit' => ['is the fit slim or regular'],
            'shipping' => ['when will you ship my order'],
            'greeting' => ['hi'],
        ];
    }

    /**
     * @dataProvider offTopicRequests
     */
    public function test_it_turns_away_requests_to_write_things(string $message): void
    {
        $this->assertTrue(
            $this->isOffTopic($message),
            "Expected the assistant to decline: \"{$message}\""
        );
    }

    /**
     * @dataProvider shopQuestions
     */
    public function test_it_lets_genuine_shop_questions_through(string $message): void
    {
        $this->assertFalse(
            $this->isOffTopic($message),
            "Expected the assistant to answer: \"{$message}\""
        );
    }

    public function test_shop_words_match_whole_words_only(): void
    {
        // "cod" inside "code" was the specific slip: it made a coding request
        // look like a question about cash on delivery.
        $this->assertTrue($this->isOffTopic('write python code'));
        $this->assertFalse($this->isOffTopic('do you accept cod'));
    }

    public function test_naming_a_product_does_not_excuse_a_writing_request(): void
    {
        $this->assertTrue($this->isOffTopic('write a poem about your shirts'));
        $this->assertFalse($this->isOffTopic('tell me about your shirts'));
    }
}
