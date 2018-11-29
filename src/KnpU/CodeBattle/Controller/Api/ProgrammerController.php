<?php

namespace KnpU\CodeBattle\Controller\Api;

use KnpU\CodeBattle\Controller\BaseController;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use KnpU\CodeBattle\Model\Programmer;

class ProgrammerController extends BaseController
{

    public function serializeProgrammer(Programmer $programmer) {
        return array(
            'nickname' => $programmer->nickname,
            'avatarNumber' => $programmer->avatarNumber,
            'powerLevel' => $programmer->powerLevel,
            'tagLine' => $programmer->tagLine,
        );
    }

    protected function addRoutes(ControllerCollection $controllers)
    {
        $controllers->post('/api/programmers', array($this, 'newAction'));
        $controllers->get('/api/programmer/{nickname}', array($this, 'showAction'))
                    ->bind('api_programmer_show');
        $controllers->get('/api/programmers', array($this, 'listAction'));
        $controllers->put('/api/programmers/{nickname}', array($this, 'updateAction'));
    }

    public function newAction(Request $request) {
        $data = json_decode($request->getContent(), true);

        $programmer = new Programmer($data['nickname'], $data['avatarNumber']);
        $programmer->tagLine = $data['tagLine'];
        $programmer->userId = $this->findUserByUsername('weaverryan')->id;

        $this->save($programmer);

        $programmerUrl = $this->generateUrl(
            'api_programmer_show',
            ['nickname' => $programmer->nickname]
        );

        $data = $this->serializeProgrammer($programmer);

        $response = new JsonResponse($data, 201);
        $response->headers->set('Location', $programmerUrl);

        return $response;
    }

    public function showAction($nickname) {
        $programmer = $this->getProgrammerRepository()->findOneByNickname($nickname);

        if (!$programmer) {
            $this->throw404('Oops ! No programmer was found under the nickname ' . $nickname);
        }

        $data = $this->serializeProgrammer($programmer);

        $response = new Response(json_encode($data), 200);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function listAction() {
        $programmers = $this->getProgrammerRepository()->findAll();
        $data = ['programmers' => array()];

        foreach ($programmers as $programmer) {
            $data['programmers'][] = $this->serializeProgrammer($programmer);
        }

        $response = new Response(json_encode($data), 200);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function updateAction($nickname, Request $request) {
        $programmer = $this->getProgrammerRepository()->findOneByNickname($nickname);

        if (!$programmer) {
            $this->throw404();
        }

        $data = json_decode($request->getContent(), true);

        $programmer->nickname = $data['nickname'];
        $programmer->avatarNumber = $data['avatarNumber'];
        $programmer->tagLine = $data['tagLine'];
        $programmer->userId = $this->findUserByUsername('weaverryan')->id;

        $this->save($programmer);

        $data = $this->serializeProgrammer($programmer);

        $response = new JsonResponse($data, 200);

        return $response;
    }

}
