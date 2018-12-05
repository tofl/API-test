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

    private function handleRequest(Request $request, Programmer $programmer) {
        $data = json_decode($request->getContent(), true);
        $isNew = !$programmer->id;

        if ($data === null) {
            throw new \Exception(sprintf('Invalid JSON: ' . $request->getContent()));
        }

        // determine which properties should be changeable on this request
        $apiProperties = array('avatarNumber', 'tagLine');
        if ($isNew) {
            $apiProperties[] = 'nickname';
        }

        // update the properties
        foreach ($apiProperties as $property) {
            if (!isset($data[$property]) && $request->isMethod('PATCH')) {
                continue;
            }
            $val = isset($data[$property]) ? $data[$property] : null;
            $programmer->$property = $val;
        }

        $programmer->userId = $this->findUserByUsername('weaverryan')->id;
    }

    protected function addRoutes(ControllerCollection $controllers)
    {
        $controllers->post('/api/programmers', array($this, 'newAction'));
        $controllers->get('/api/programmer/{nickname}', array($this, 'showAction'))
                    ->bind('api_programmer_show');
        $controllers->get('/api/programmers', array($this, 'listAction'));
        $controllers->put('/api/programmers/{nickname}', array($this, 'updateAction'));
        $controllers->delete('/api/programmers/{nickname}', array($this, 'deleteAction'));
        $controllers->match('/api/programmers/{nickname}', array($this, 'updateAction'))
                    ->method('PATCH');
    }

    public function newAction(Request $request) {
        $data = json_decode($request->getContent(), true);

        $programmer = new Programmer();
        $this->handleRequest($request, $programmer);


        $programmerFound = $this->getProgrammerRepository()->findOneByNickname($data['nickname']);
        if ($programmerFound) {
            $data = [
                'type' => 'validation_error',
                'title' => 'There was a validation error',
                'errors' => 'nickname already exists'
            ];
            return new JsonResponse($data, 400);
        }


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

        $this->handleRequest($request, $programmer);
        $this->save($programmer);

        $data = $this->serializeProgrammer($programmer);

        $response = new JsonResponse($data, 200);

        return $response;
    }

    public function deleteAction($nickname, Request $request) {
        $programmer = $this->getProgrammerRepository()->findOneByNickname($nickname);

        if ($programmer) {
            $this->delete($programmer);
        }

        return new Response(null, 204);
    }

}
