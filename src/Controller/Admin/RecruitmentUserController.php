<?php
namespace App\Controller\Admin;

use App\Entity\RecruitmentUsers;
use App\Entity\RecruitmentUserStatus;
use App\Form\RecruitmentUsersAdminType;
use App\Utils\MailManagerUtils;
use App\Utils\TcpdfUtils;
use Doctrine\ORM\EntityManagerInterface;
use ProxyManager\Exception\FileNotWritableException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use App\Form\RecruitmentUserType;

/**
 * Class RecruitmentUserController
 * @package App\Controller\Admin
 */
class RecruitmentUserController extends Controller
{
    /**
     * @var Session
     */
    private $session;

    /**
     * RecruitmentUserController constructor.
     */
    public function __construct()
    {
        $this->session = new Session();
    }

    /**
     * @param int $id
     * @return Response
     */
    public function show(int $id)
    {
        $recruitmentUser = $this->getDoctrine()
            ->getRepository(RecruitmentUsers::class)
            ->find($id);


        return $this->render('back/recruitment_user_show.html.twig',array(
            'recruitment_user'=> $recruitmentUser
        ));
    }

    /**
     * @param int $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function edit(int $id, Request $request)
    {
        $recruitmentUser = $this->getDoctrine()
            ->getRepository(RecruitmentUsers::class)
            ->find($id);
        if($recruitmentUser){
            $form = $this->createForm(RecruitmentUsersAdminType::class, $recruitmentUser);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $recruitmentUser = $form->getData();

                $em = $this->getDoctrine()->getManager();
                $em->persist($recruitmentUser);
                $em->flush();

                $this->session->getFlashBag()->add('success', 'Nabór został zmieniony');

                return $this->redirectToRoute('admin_recruitment_user_edit',['id'=> $id]);
            }

            return $this->render('back/recruitment_user_edit.html.twig',array(
                'recruitment_users'=> $recruitmentUser,
                'form'=> $form->createView()
            ));
        }else {
            $this->session->getFlashBag()->add('danger', 'Nabór nie została znaleziona');

            return $this->redirectToRoute('admin_recruitment_users');
        }

    }

    /**
     * @param $id
     * @param $status
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function editStatus($id, $status, \Swift_Mailer $mailer, EntityManagerInterface $emi)
    {
        $recruitmentUser = $this->getDoctrine()
            ->getRepository(RecruitmentUsers::class)
            ->find($id);
        if($recruitmentUser){
            $recruitmentUserStatus = $this->getDoctrine()
                ->getRepository(RecruitmentUserStatus::class)
                ->find($status);
            if($recruitmentUserStatus){
                $recruitmentUser->setStatus($recruitmentUserStatus);

                $em = $this->getDoctrine()->getManager();
                $em->persist($recruitmentUser);
                $em->flush();

                if($recruitmentUserStatus->getIsFvGenerated() == 1){
                    try{
                        $this->generateAgreement($recruitmentUser->getId());
                    }catch(FileException $exception){
                        echo 'Umowa już istnieje'. $exception->getMessage();
                    }
                }
                if($recruitmentUserStatus->getIsMailed() == 1){
                    $mailManager = new MailManagerUtils($emi);
                    $template = 'emails/' . $recruitmentUserStatus->getMailTemplate();
                    $mailBody = $this->renderView($template,[
                        'recruitment' => $recruitmentUser,
                    ]);
                    if(!$mailBody){
                        throw new FileNotFoundException($template);
                    }
                    $user = $recruitmentUser->getUser();
                    $name = $user->getFirstName() . ' ' .$user->getLastName();
                    $mailBodyPersonalized = str_replace('user',$name, $mailBody);

                    if($recruitmentUserStatus->getIsFvMailed() == 1){
                       $file = $recruitmentUser->getAbsoluteAgreementPath();
                    }
                    $mailManager->sendEmail($mailBodyPersonalized,['subject' => '4eliteinvestments - Status twojej oferty uległ zmianie'],$user->getEmail(),$mailer,$file);
                }
                $this->session->getFlashBag()->add('success', 'Status oferty został zmieniony');

                return $this->redirectToRoute('admin_recruitment_show',['id' => $recruitmentUser->getRecruitment()->getId()]);

            }else {
                $this->session->getFlashBag()->add('danger', 'Status oferty nie został zmieniony');
            }
        }else {
            $this->session->getFlashBag()->add('danger', 'Status oferty nie został zmieniony');
        }

        return $this->redirectToRoute('admin_recruitment_users');
    }

    /**
     * @return Response
     */
    public function disable(int $id, int $status)
    {
        $recruitmentUser = $this->getDoctrine()
            ->getRepository(RecruitmentUsers::class)
            ->find($id);
        $recruitmentUser->setIsActive($status);

        $em = $this->getDoctrine()->getManager();
        $em->persist($recruitmentUser);
        $em->flush();

        $this->session->getFlashBag()->add('success', 'Oferta została wyłączona');

        return $this->redirectToRoute('admin_recruitments');
    }

    /**
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delete(int $id)    {
        $recruitmentUser = $this->getDoctrine()
            ->getRepository(RecruitmentUsers::class)
            ->find($id);

        $em = $this->getDoctrine()->getManager();
        $em->remove($recruitmentUser);
        $em->flush();

        $this->session->getFlashBag()->add('success', 'Nabór został usunięty');

        return $this->redirectToRoute('admin_recruitment_users');
    }

    /**
     * @return Response
     */
    public function index(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $query = $em->getRepository(RecruitmentUsers::class)->findBy([],[
            $request->query->get('sort','id')=>$request->query->get('direction','asc')
        ]);
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1)
        );

        $recruitmentUserStatus = $this->getDoctrine()
            ->getRepository(RecruitmentUserStatus::class)
            ->findBy(['isActive'=>1]);

        return $this->render('back/recruitment_users.html.twig',array(
            'recruitmentUsers'=> $pagination,
            'recruitmentUserStatus' => $recruitmentUserStatus
        ));
    }

    public function getAgreement($id)
    {
        $recruitmentUser = $this->getDoctrine()
            ->getRepository(RecruitmentUsers::class)
            ->findOneBy(['id' => $id]);

        return $this->file($recruitmentUser->getAbsoluteAgreementPath(),$recruitmentUser->getNumber());
    }

    private function generateAgreement($recruitmentUserId)
    {
        $tcpdf = new TcpdfUtils();
        $recruitmentUser = $this->getDoctrine()
            ->getRepository(RecruitmentUsers::class)
            ->find($recruitmentUserId);

        $fileSystem = new Filesystem();
        $filePath = $recruitmentUser->getUploadRootDir();
        $fileName = $recruitmentUser->getNumber() . '-' . mt_rand() .'.pdf';
        $recruitmentUser->setAgreementPath($fileName);
        $recruitmentUser->setAcceptedDate(new \DateTime('now'));
        $em = $this->getDoctrine()->getManager();
        $em->persist($recruitmentUser);
        $em->flush();

        if(!$fileSystem->exists($filePath)){
            try{
                $fileSystem->mkdir($filePath);
            }catch (IOException $exception){
                echo 'Error in dir creation'. $exception->getPath() . ' ' . $exception->getPath();
            }
        }
        $tcpdf->AddPage();
        $html = $this->renderView('agreement/agreement.html.twig',[
            'recruitment' => $recruitmentUser->getRecruitment(),
            'recruitmentUser' => $recruitmentUser,
        ]);
        $tcpdf->writeHTML($html);
        try{
            $tcpdf->Output($recruitmentUser->getAbsoluteAgreementPath(), 'F');
        } catch (FileNotWritableException $exception){
            echo 'Agreement was not created: '. $exception->getMessage();
        }



        return true;
    }
}
