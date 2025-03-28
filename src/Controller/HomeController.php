<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SubCategoryRepository;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(ProductRepository $productRepository, CategoryRepository $categoryRepository, Request $request, PaginatorInterface $paginator): Response
    {
        //Pour la pagination des pages
        $data =  $productRepository->findBy([],);
        $products = $paginator->paginate(
            $data,
            $request->query->getInt('page', default: 1),
            limit: 8
        );
        return $this->render('home/index.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findAll()
        ]);
    }

    //Cette fonction permette  de séléctionner un produit spécifique
    #[Route('/home/product/{id}/show', name: 'app_home_product_show', methods: ['GET'])]
    public function show(Product $product, ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        //Afficher les cinq(05) derniers produits
        $dernierProduit = $productRepository->findBy([], ['id' => 'DESC'], 5);
        return $this->render('home/show.html.twig', [
            'product' => $product,
            'products' => $dernierProduit,
            'categories' => $categoryRepository->findAll()
        ]);
    }
    // //Cette fonction permette de filtrer les produits
    #[Route('/home/product/subCategory/{id}/filter', name: 'app_home_product_filter', methods: ['GET'])]
    public function filter($id, SubCategoryRepository $subCategoryRepository, CategoryRepository $categoryRepository): Response
    {
        $products = $subCategoryRepository->find($id)->getProducts();
        //Recuperer le nom du sous categori
        $subCategory = $subCategoryRepository->find($id);

        return $this->render('home/filter.html.twig', [
            'products' => $products,
            'subCategory' => $subCategory,
            'categories' => $categoryRepository->findAll()
        ]);
    }
}
