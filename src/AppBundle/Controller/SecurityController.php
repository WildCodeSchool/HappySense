<?php
namespace AppBundle\Controller;
use AppBundle\Entity\ChangePwd;
use AppBundle\Form\ChangePwdType;
use AppBundle\Service\CheckSecurityService;
use AppBundle\Service\EmailService;
use Faker\Provider\DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends Controller
{
    /**
     * @Route("/login", name="login")
     */
    public function loginAction(Request $request, AuthenticationUtils $authUtils)
    {
        // Login erreur
        $error = $authUtils->getLastAuthenticationError();

        // dernier nom entre par le user
        $lastUsername = $authUtils->getLastUsername();
        return $this->render('pages/In/security/login.html.twig', array(
            'last_username' => $lastUsername,
            'error'         => $error,
        ));
    }

    /**
     * @Route("/sendChange", name="sendChange")
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @param EmailService $emailService
     * @param CheckSecurityService $checkSecurityService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function sendChangeAction(Request $request, EmailService $emailService, CheckSecurityService $checkSecurityService)
    {
        $changePwd = new Changepwd();
        $form = $this->createForm('AppBundle\Form\ChangePwdType', $changePwd);
        $form->remove('token')
             ->remove('idUser')
             ->remove('dateSend');
        $form->handleRequest($request);
        $errors = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $mail = $changePwd->getEmail();
            $user = $em->getRepository('AppBundle:User')->findByEmail($mail);
            foreach ($user as $item) {
                if ($mail === $item->getEmail()) {
                    $changePwd->setIdUser($item->getId())
                              ->setEmail($item->getEmail())
                              ->setDateSend(new \DateTime('now'))
                              ->setToken($item->getFirstName(), $mail, $item->getId());
                    $changePwd->setIsActive(false);
                    $email_contact = $this->container->getParameter('email_contact');
                    $emailService->sendMailNewPwd($mail, $email_contact, $item->getFirstName(), $item->getLastName(), $changePwd->getToken());
                } else {
                    $errors += ['Adresse email invalide, réessayer.'];
                    return $this->render('pages/In/security/send.html.twig', array(
                        'changePwd' => $changePwd,
                        'errors' => $errors,
                        'form' => $form->createView(),
                    ));
                }
            }
            $em->persist($changePwd);
            $em->flush();
            return $this->render('pages/In/security/login.html.twig', array(
                'changePwd' => $changePwd,
                'form' => $form->createView(),
            ));
        }

        return $this->render('pages/In/security/send.html.twig', array(
            'changePwd' => $changePwd,
            'errors' => $errors,
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/change/{token}", name="change")
     * @Method("GET")
     */
    public function changeAction(Request $request)
    {

//        return $this->render('pages/In/security/change.html.twig', {  });
    }


}