<?php
namespace App\Controller\Admin;

use App\Entity\Module;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use App\Form\ModuleType;

class ModuleController extends Controller
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
        $modules = $this->getDoctrine()
            ->getRepository(Module::class)
            ->findAll();

        return $this->render('back/modules.html.twig',array(
            'modules'=> $modules
        ));
    }

    /**
     * @param $id
     * @return Response
     */
    public function show($id)
    {
        $module = $this->getDoctrine()
            ->getRepository(Module::class)
            ->find($id);

        return $this->render('back/module_genus_show.html.twig',array(
            'module'=> $module
        ));
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function new(Request $request){

        $module = new Module();
        $form = $this->createForm(ModuleType::class, $module);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($module);
            $em->flush();

            $this->session->getFlashBag()->add('success', 'Nowy moduł został stworzony');

            return $this->redirectToRoute('admin_module_edit',array('id'=>$module->getId()));
        }

        return $this->render('back/module_new.html.twig',array(
            'form'=> $form->createView()
        ));
    }

    /**
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function edit($id, Request $request)    {
        $module = $this->getDoctrine()
            ->getRepository(Module::class)
            ->find($id);
        if($module){
            $form = $this->createForm(ModuleType::class, $module);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $module = $form->getData();

                $em = $this->getDoctrine()->getManager();
                $em->persist($module);
                $em->flush();

                $this->session->getFlashBag()->add('success', 'Moduł został zminiony');

                return $this->render('back/module_edit.html.twig',array(
                    'module'=> $module,
                    'form'=> $form->createView()
                ));
            }

            return $this->render('back/module_edit.html.twig',array(
                'module'=> $module,
                'form'=> $form->createView()
            ));
        }else {

            $this->session->getFlashBag()->add('danger', 'Moduł nie został znaleziony');

            return $this->redirectToRoute('admin_module');
        }
    }

    public function disable($id, $status)    {
        $module = $this->getDoctrine()
            ->getRepository(Module::class)
            ->find($id);
        $module->setIsActive($status);

        $em = $this->getDoctrine()->getManager();
        $em->persist($module);
        $em->flush();

        $this->session->getFlashBag()->add('success', 'Moduł został wyłączony');

        return $this->redirectToRoute('admin_modules');
    }

    public function delete($id)    {
        $module = $this->getDoctrine()
            ->getRepository(Module::class)
            ->find($id);

        $em = $this->getDoctrine()->getManager();
        $em->remove($module);
        $em->flush();

        $this->session->getFlashBag()->add('success', 'Moduł został usunięty');

        return $this->redirectToRoute('admin_modules');
    }
}
