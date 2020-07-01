<?php

namespace App\Controller;

class CatalogController implements \ContainerAwareInterface
{
    public function viewAction(string $id)
    {
        return new JsonResponse(['data' => '']);
    }

    public function listAction(Request $request)
    {
        return new Response('ciao');
    }
}