<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FactureController extends AbstractController
{
    #[Route('/editor/order/{id}/facture', name: 'app_facture')]
    public function index($id, OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->find($id);

        $pdfOption = new Options();
        $pdfOption->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($pdfOption);


        $html = $this->renderView('facture/index.html.twig', [
            'order' => $order
        ]);

        $dompdf->loadHtml($html);
        $dompdf->render();
        $dompdf->stream("my-symfony-e-commerce-app-facture-". $order->getId(). '-pdf', [
            'Attachment' => false
        ]);

        return new Response('', '200', [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
