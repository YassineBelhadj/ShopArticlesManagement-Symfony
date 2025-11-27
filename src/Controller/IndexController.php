<?php
namespace App\Controller;
use App\Form\ArticleType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Article;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Category;
use App\Form\CategoryType;
use App\Entity\PropertySearch;
use App\Form\PropertySearchType;
use App\Entity\CategorySearch;
use App\Form\CategorySearchType;
use App\Entity\PriceSearch;
use App\Form\PriceSearchType;
class IndexController extends AbstractController
{
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

 #[Route('/', name: 'home')]
    public function home(Request $request)
    {
        $propertySearch = new PropertySearch();
        $form = $this->createForm(PropertySearchType::class, $propertySearch);
        $form->handleRequest($request);

        $articles = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $nom = $propertySearch->getNom();
            if ($nom != "") {
                $articles = $this->doctrine->getRepository(Article::class)->findBy(['nom' => $nom]);
            } else {
                $articles = $this->doctrine->getRepository(Article::class)->findAll();
            }
        } else {
            // Afficher tous les articles au premier chargement
            $articles = $this->doctrine->getRepository(Article::class)->findAll();
        }

        return $this->render('articles/index.html.twig', [
            'form' => $form->createView(),
            'articles' => $articles,
        ]);
    }

    #[Route('/article/save', name: 'article_save')]
    public function save()
    {
        $entityManager = $this->doctrine->getManager();
        $article = new Article();
        $article->setNom('Article 1');
        $article->setPrix(1000);
        $entityManager->persist($article);
        $entityManager->flush();
        return new Response('Article enregistré avec id ' . $article->getId());
    }

    #[Route('/article/new', name: 'new_article', methods: ['GET', 'POST'])]
    public function new(Request $request)
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class,$article);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
        $article = $form->getData();
        $entityManager = $this->doctrine->getManager();
        $entityManager->persist($article);
        $entityManager->flush();
        return $this->redirectToRoute('home');
        }
        return $this->render('articles/new.html.twig',['form' => $form->createView()]);
    }

    #[Route('/article/{id}', name: 'article_show')]
    public function show($id)
    {
        $article = $this->doctrine->getRepository(Article::class)->find($id); //
        return $this->render('articles/show.html.twig', ['article' => $article]);
    }

    #[Route('/article/edit/{id}', name: 'edit_article', methods: ['GET', 'POST'])]
        public function edit(Request $request, $id)
        {
        $article = new Article();
        $article = $this->doctrine->getRepository(Article::class)->find($id);
        $form = $this->createForm(ArticleType::class,$article);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $this->doctrine->getManager();
            $entityManager->flush();
            return $this->redirectToRoute('home');
        }
        return $this->render('articles/edit.html.twig', ['form' =>$form->createView()]);
        }


    #[Route('/article/delete/{id}', name: 'delete_article', methods: ['GET'])]
    public function delete(Request $request, $id)
    {
        $article = $this->doctrine->getRepository(Article::class)->find($id);
        $entityManager = $this->doctrine->getManager();
        $entityManager->remove($article);
        $entityManager->flush();

        return $this->redirectToRoute('home');
    }

    #[Route('/category/new', name: 'new_category', methods: ['GET','POST'])]
    public function newCategory(Request $request) {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($category);
            $entityManager->flush();

            return $this->redirectToRoute('home');
        }

        return $this->render('articles/newCategory.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/art_cat/', name: 'article_par_cat', methods: ['GET', 'POST'])]
    public function articlesParCategorie(Request $request)
    {
        $categorySearch = new CategorySearch();
        $form = $this->createForm(CategorySearchType::class, $categorySearch);
        $form->handleRequest($request);

        $articles = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $category = $categorySearch->getCategory();
            if ($category != "") {
                $articles = $category->getArticles();
            } else {
                $articles = $this->doctrine->getRepository(Article::class)->findAll();
            }
        }

        return $this->render('articles/articlesParCategorie.html.twig', [
            'form' => $form->createView(),
            'articles' => $articles,
        ]);
    }

    #[Route('/art_prix/', name: 'article_par_prix', methods: ['GET', 'POST'])]
    public function articlesParPrix(Request $request)
    {
        $priceSearch = new PriceSearch();
        $form = $this->createForm(PriceSearchType::class, $priceSearch);
        $form->handleRequest($request);

        $articles = [];
        if($form->isSubmitted() && $form->isValid()) {
            $minPrice = $priceSearch->getMinPrice();
            $maxPrice = $priceSearch->getMaxPrice();
            $articles = $this->doctrine->getRepository(Article::class)->findByPriceRange($minPrice, $maxPrice);
        }

        return $this->render('articles/articlesParPrix.html.twig', [
            'form' => $form->createView(),
            'articles' => $articles
        ]);
    }


}
