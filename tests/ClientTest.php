<?php

namespace BendeckDavid\GraphqlClient\Tests;

use BendeckDavid\GraphqlClient\Classes\FileGetContentsWrapper;
use \Orchestra\Testbench\TestCase;
use BendeckDavid\GraphqlClient\Classes\Client;
use Mockery;

class ClientTest extends TestCase
{
    protected Client $client;
    protected $mockFileGetter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockFileGetter = Mockery::mock('overload:BendeckDavid\GraphqlClient\Classes\FileGetContentsWrapper');
        $this->mockFileGetter->shouldReceive('__invoke')->andReturn('{"data": {"users": [{"id": 1, "name": "John"}]}}');
        $this->app->instance(FileGetContentsWrapper::class, $this->mockFileGetter);
        // Merge the config file
        $this->app['config']->set('graphqlclient', require __DIR__.'/../config/config.php');

        $this->client = new Client('http://example.com/graphql', 'test_token', 'bearer');
    }

    public function testDefaultAuthScheme()
    {
        $expectedHeaders = ["Content-Type: application/json", "User-Agent: Laravel GraphQL client", "Authorization: Bearer test_token"];
        $client = new Client('http://example.com/graphql', 'test_token');
        $this->assertEquals($expectedHeaders, $client->getHeadersAttribute());
    }

    public function testRuntimeAuthScheme()
    {
        $expectedHeaders = ["Content-Type: application/json", "User-Agent: Laravel GraphQL client", "Authorization: Basic test_token"];
        $client = new Client('http://example.com/graphql', 'test_token', 'basic');

        $this->assertEquals($expectedHeaders, $client->getHeadersAttribute());
    }

    public function testQueryGeneration()
    {
        $query = 'users { id name }';
        $this->client->query($query);
        $this->assertEquals("query {{$query}}", $this->client->getRawQueryAttribute());
    }

    public function testMutationGeneration()
    {
        $mutation = 'createUser(name: "John") { id name }';
        $this->client->mutation($mutation);
        $this->assertEquals("mutation {{$mutation}}", $this->client->getRawQueryAttribute());
    }

    public function testRawQueryGeneration()
    {
        $rawQuery = 'query { users { id name } }';
        $this->client->raw($rawQuery);
        $this->assertEquals($rawQuery, $this->client->getRawQueryAttribute());
    }

    public function testHeaders()
    {
        $this->client->header('X-Custom-Header', 'CustomValue');
        $headers = $this->client->getHeadersAttribute();
        $this->assertContains('X-Custom-Header: CustomValue', $headers);
    }

    public function testWithHeaders()
    {
        $headers = [
            'X-Header-One' => 'ValueOne',
            'X-Header-Two' => 'ValueTwo'
        ];
        $this->client->withHeaders($headers);
        $formattedHeaders = $this->client->getHeadersAttribute();
        $this->assertContains('X-Header-One: ValueOne', $formattedHeaders);
        $this->assertContains('X-Header-Two: ValueTwo', $formattedHeaders);
    }
    public function testInvalidAuthScheme()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid Graphql Client Auth Scheme');
        $client = new Client('http://example.com/graphql', 'test_token', 'invalid_scheme');
        $client->getHeadersAttribute();
    }

    public function testWithVariables()
    {
        $variables = ['id' => 1, 'name' => 'John'];
        $this->client->with($variables);
        $this->assertEquals($variables, $this->client->variables);
    }

    public function testMakeRequest()
    {
        $this->markTestSkipped('file_get_contents needs replacing with a class or something.');
        $query = 'query { users { id name } }';
        $this->mockFileGetter->shouldReceive('__invoke')->once()->andReturn('{"data": {"users": [{"id": 1, "name": "John"}]}}');
        $response = $this->client->raw($query)->get();
    }

}