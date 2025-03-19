<?php

namespace App\Controller;

use App\Entity\AddProductHistory;
use App\Entity\Product;
use App\Form\AddProductHistoryType;
use App\Form\ProductType;
use App\Repository\AddProductHistoryRepository;
use App\Repository\ProductRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Form\ProductUpdateType;


use function PHPSTORM_META\type;

#[Route('/editor/product')]
final class ProductController extends AbstractController
{
    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //permet de recuperer l'image
            $image = $form->get('image')->getData();
            //virifier si l'image existe
            if ($image) {
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                //supprimer les espaces entres les noms des images et remplacer par tiré
                $safeFileName = $slugger->slug($originalName);
                //permet de renommer l'images
                $newFileName = $safeFileName . '-' . uniqid() . '.' . $image->guessExtension();

                //repertoir pour sauvegarder l'image
                try {
                    //Déplacer l'image dans le repertoire temporaire
                    $image->move(
                        $this->getParameter('image_dir'),
                        $newFileName
                    );
                } catch (FileException $exception) {
                }

                $product->setImage($newFileName);
            }
            $entityManager->persist($product);
            $entityManager->flush();

            //Cet bloc permet la gestion du stock de l'historique des produits
            $stockHistory = new AddProductHistory(); //cette variable stcke historique des produits
            $stockHistory->setQte($product->getStock()); // on recupere historique des produits
            $stockHistory->setProduct($product); //inserer le produit
            $stockHistory->setCreatedAt(new DateTimeImmutable()); //la date  du produit
            $entityManager->persist($stockHistory);
            $entityManager->flush();

            $this->addFlash(type: 'success', message: 'votre produit a été ajouté');
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ProductUpdateType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $image = $form->get('image')->getData();
            //virifier si l'image existe
            if ($image) {
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                //supprimer les espaces entres les noms des images et remplacer par tiré
                $safeFileName = $slugger->slug($originalName);
                //permet de renommer l'images
                $newFileName = $safeFileName . '-' . uniqid() . '.' . $image->guessExtension();

                //repertoir pour sauvegarder l'image
                try {
                    //Déplacer l'image dans le repertoire temporaire
                    $image->move(
                        $this->getParameter('image_dir'),
                        $newFileName
                    );
                } catch (FileException $exception) {
                }

                $product->setImage($newFileName);
            }
            $entityManager->flush();

            $this->addFlash(type: 'success', message: 'votre produit a été modifié');
            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($product);

            $this->addFlash(type: 'danger', message: 'votre produit a été supprimé');
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    // cette function permet de stoker historique des stocks de produit
    #[Route('/add/product/{id}/stock', name: 'app_product_stock_add', methods: ['POST', 'GET'])]
    public function addStock($id, EntityManagerInterface $entityManager, Request $request, ProductRepository $productRepository): Response
    {
        $addStock = new AddProductHistory();
        $form = $this->createForm(AddProductHistoryType::class, $addStock);
        $form->handleRequest($request);

        //Recupérer le produit
        $product = $productRepository->find($id);

        //soumettrele formulair
        if ($form->isSubmitted() && $form->isValid()) {

            //une petite verification
            if ($addStock->getQte() > 0) {
                //faire la mise  a jours au niveau du champ produit
                $newQte = $product->getStock() + $addStock->getQte();
                $product->setStock($newQte);

                //Stocker historique
                $addStock->setCreatedAt(new DateTimeImmutable());
                $addStock->setProduct($product);
                $entityManager->persist($addStock);
                $entityManager->flush();

                $this->addFlash(type: 'success', message: 'Le stocke de votre produit a été modifier');
                return $this->redirectToRoute("app_product_index");
            } else {
                $this->addFlash(type: 'danger', message: 'Le stock ne doit pas étre inférieur a 0');
                return $this->redirectToRoute("app_product_stock_add", ['id' => $product->getId()]);
            }
        }

        return $this->render(
            'product/addStock.html.twig',
            [
                'form' => $form->createView(),
                'product' => $product
            ],
        );
    }
    //Cette function permet de recuperer l'historique des stoks
    #[Route('/add/product/{id}/stock/history', name: 'app_product_stock_add_history', methods: ['GET'])]
    public function productAddHistory($id, ProductRepository $productRepository, AddProductHistoryRepository $addProductHistory): Response
    {
        //Recupérer le produit stoker
        $product = $productRepository->find($id);
        $productAddedHistory = $addProductHistory->findBy(['product' => $product], ['id' => 'DESC']);
        //dd($productAddedHistory);

        return $this->render('product/addedStockHistory.html.twig', [
            'productsAdded' => $productAddedHistory,
        ]);
    }
}
