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
    // Définition de la route pour générer une facture à partir d'un ID de commande
    #[Route('/editor/order/{id}/facture', name: 'app_facture')]
    public function index($id, OrderRepository $orderRepository): Response
    {
        // Récupération de la commande correspondante à l'ID fourni
        $order = $orderRepository->find($id);

        // Configuration des options pour la génération du PDF
        $pdfOption = new Options();
        $pdfOption->set('defaultFont', 'Arial');

        // Création d'une instance de Dompdf avec les options définies
        $dompdf = new Dompdf($pdfOption);

        // Rendu du template de facture avec les données de la commande
        $html = $this->renderView('facture/index.html.twig', [
            'order' => $order
        ]);

        // Chargement du HTML généré dans Dompdf
        $dompdf->loadHtml($html);

        // Génération du fichier PDF
        $dompdf->render();

        // Envoi du fichier PDF au navigateur sans forcer le téléchargement
        $dompdf->stream("my-symfony-e-commerce-app-facture-" . $order->getId() . '-pdf', [
            'Attachment' => false
        ]);

        // Retourne une réponse HTTP avec un en-tête précisant le type de contenu PDF
        return new Response('', '200', [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
