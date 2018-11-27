<?php

namespace KnpU\CodeBattle\Tests;

use Guzzle;

class ProgrammerControllerTest extends \PHPUnit\Framework\TestCase
{
    public function testPost() {
        // create our http client (Guzzle)
        $client = new Guzzle\Http\Client('http://localhost', array(
            'request.options' => array(
                'exceptions' => false,
            )
        ));

        $nickname = 'ObjectOrienter'.rand(0, 999);
        $data = array(
            'nickname' => $nickname,
            'avatarNumber' => 5,
            'tagLine' => 'a test dev!'
        );

        $request = $client->post('/api/programmers', null, json_encode($data));
        $response = $request->send();

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Location'));
        $data = json_decode($response->getBody(true), true);
        $this->assertArrayHasKey('nickname', $data);
    }
}