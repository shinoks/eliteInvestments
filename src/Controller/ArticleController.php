<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use App\Entity\Article;
use App\Entity\Comment;
use App\Form\CommentType;

class ArticleController extends Controller
{
    private $session;

    public function __construct()
    {
        $this->session = new Session();
    }

    /**
     * @return Response
     */
    public function index()
    {
        $articles = $this->getDoctrine()
            ->getRepository(Article::class)
            ->findAll();

        return $this->render('front/articles.html.twig',array(
            'articles'=> $articles
        ));
    }

    /**
     * @return Response
     */
    public function show($id,Request $request)
    {
        $article = $this->getDoctrine()
            ->getRepository(Article::class)
            ->find($id);

        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($comment);
            $em->flush();

            $this->session->getFlashBag()->add('success', 'Komentarz został́ dodany');
        }
        return $this->render('front/article_show.html.twig',array(
            'article'=> $article
        ));
    }

    /**
     * @return Response
     */
    public function showByCategory($categoryId)
    {
        $articles = $this->getDoctrine()
            ->getRepository(Article::class)
            ->findBy(['category' => $categoryId]);

        return $this->render('front/articles.html.twig',array(
            'articles'=> $articles
        ));
    }

}
