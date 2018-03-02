<?php
namespace App\Controller\Admin;

use App\Entity\Menu;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use App\Form\MenuType;

class MenuController extends Controller
{
    private $session;

    public function __construct()
    {
        $this->session = new Session();
    }

    /**
     * @return Response
     */
    public function show($id)
    {
        $menu = $this->getDoctrine()
            ->getRepository(Menu::class)
            ->find($id);

        return $this->render('back/menu_show.html.twig',array(
            'menu'=> $menu
        ));
    }

    /**
     * @return Response
     */
    public function edit($id, Request $request)    {
        $menu = $this->getDoctrine()
            ->getRepository(Menu::class)
            ->find($id);

        if($menu){
            $form = $this->createForm(MenuType::class, $menu);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $menu = $form->getData();

                $em = $this->getDoctrine()->getManager();
                $em->persist($menu);
                $em->flush();

                $this->session->getFlashBag()->add('success', 'Pozycja menu została zmieniona');

                return $this->redirectToRoute('admin_menu_edit',['id'=>$id]);
            }

            return $this->render('back/menu_edit.html.twig',array(
                'menu'=> $menu,
                'form'=> $form->createView()
            ));
        }else {
            $this->session->getFlashBag()->add('danger', 'Pozycja menu nie została zmieniona');

            return $this->redirectToRoute('admin_menus');
        }
    }

    /**
     * @return Response
     */
    public function new(Request $request){

        $menu = new Menu();
        $form = $this->createForm(MenuType::class, $menu);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($menu);
            $em->flush();

            $this->session->getFlashBag()->add('success', 'Pozycja menu została dodana');

            return $this->redirectToRoute('admin_menu_edit',array('id'=>$menu->getId()));
        }

        return $this->render('back/menu_new.html.twig',array(
            'form'=> $form->createView()
        ));
    }

    /**
     * @return Response
     */
    public function disable($id, $status)    {
        $menu = $this->getDoctrine()
            ->getRepository(Menu::class)
            ->find($id);
        $menu->setIsActive($status);

        $em = $this->getDoctrine()->getManager();
        $em->persist($menu);
        $em->flush();

        if($status == 1){
            $this->session->getFlashBag()->add('success', 'Pozycja menu została wyłączona');
        }else {
            $this->session->getFlashBag()->add('success', 'Pozycja menu nie została wyłączona');
        }

        return $this->redirectToRoute('admin_menus');
    }

    /**
     * @return Response
     */
    public function delete($id)    {
        $menu = $this->getDoctrine()
            ->getRepository(Menu::class)
            ->find($id);

        $em = $this->getDoctrine()->getManager();
        $em->remove($menu);
        $em->flush();

        $this->session->getFlashBag()->add('success', 'Pozycja menu została usunięta');

        return $this->redirectToRoute('admin_menus');
    }

    /**
     * @return Response
     */
    public function index()
    {
        $menus = $this->getDoctrine()
            ->getRepository(Menu::class)
            ->findBy(['parent' => null]);

        return $this->render('back/menus.html.twig',array(
            'menus'=> $menus
        ));
    }
}