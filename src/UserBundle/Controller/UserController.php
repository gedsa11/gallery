<?php

namespace UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;
use UserBundle\Entity\User;
use UserBundle\Form\UserType;

class UserController extends Controller
{
    public function homeAction()
    {
        
    	$em= $this->getDoctrine()->getManager();
    	
    	//$images= $em->getRepository('UserBundle:Image')->findAll();
    	
    	$query = $em->createQuery(
            'SELECT i.image,i.title,i.description,i.id
            FROM UserBundle:Image i
            WHERE i.status = 1'    
        );
        
        $images = $query->getResult();
    	
    	///
        return $this->render('UserBundle:User:home.html.twig', array('images' => $images));

    }
    public function indexAction()
    {
        
    	$em= $this->getDoctrine()->getManager();
    	$users= $em->getRepository('UserBundle:User')->findAll();
    	return $this->render('UserBundle:User:index.html.twig', array('users' => $users));
    }

    public function addAction()
    {
        $user = new User();
        $form = $this->createCreateForm($user);
        
        return $this->render('UserBundle:User:add.html.twig', array('form' => $form->createView()));
    }
    
    private function createCreateForm(User $entity)
    {
        $form = $this->createForm(new UserType(), $entity, array(
                'action' => $this->generateUrl('user_create'),
                'method' => 'POST'
            ));
        
        return $form;
    }

     public function createAction(Request $request)
    {   
        $user = new User();
        $form = $this->createCreateForm($user);
        $form->handleRequest($request);
        
        if($form->isValid())
        {
            $password = $form->get('password')->getData();
            
            $passwordConstraint = new Assert\NotBlank();
            $errorList = $this->get('validator')->validate($password, $passwordConstraint);
            
            if(count($errorList) == 0)
            {
				$encoder = $this->container->get('security.password_encoder');
	            $encoded = $encoder->encodePassword($user,$password);

	            $user->setPassword($encoded);
	            $user->setUpdateAt(new \DateTime());

	            $em = $this->getDoctrine()->getManager();
	            $em->persist($user);
	            $em->flush();

	            $this->addFlash('mensaje', '¡Usuario creado con éxito!');

	            return $this->redirectToRoute('user_login');
	        }
	        else
            {
                $errorMessage = new FormError($errorList[0]->getMessage());
                $form->get('password')->addError($errorMessage);
            }

        }
        
        return $this->render('UserBundle:User:add.html.twig', array('form' => $form->createView()));
    }

    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('UserBundle:User')->find($id);
        
        if(!$user)
        {
            throw $this->createNotFoundException('User not found.');
        }
        
        $form = $this->createEditForm($user);
        
        return $this->render('UserBundle:User:edit.html.twig', array('user' => $user, 'form' => $form->createView()));
        
    }

    private function createEditForm(User $entity)
    {
        $form = $this->createForm(new UserType(), $entity, array('action' => $this->generateUrl('user_update', array('id' => $entity->getId())), 'method' => 'PUT'));
        
        return $form;
    }
    public function updateAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        
        $user = $em->getRepository('UserBundle:User')->find($id);

        if(!$user)
        {
            throw $this->createNotFoundException('User not found.');
        }
        
        $form = $this->createEditForm($user);
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid())
        {
            $password = $form->get('password')->getData();
            if(!empty($password))
            {
                $encoder = $this->container->get('security.password_encoder');
                $encoded = $encoder->encodePassword($user, $password);
                $user->setPassword($encoded);
            }
            else
            {
                $recoverPass = $this->recoverPass($id);
                $user->setPassword($recoverPass[0]['password']);                
            }
            
           if($form->get('role')->getData() == 'ROLE_ADMIN')
            {
                $user->setIsActive(1);
            }

            $user->setUpdateAt(new \DateTime());
            $em->flush();
            
            $this->addFlash('mensaje', 'El usuario ha sido modificado.');
            return $this->redirectToRoute('user_index', array('id' => $user->getId()));
        }
        return $this->render('UserBundle:User:edit.html.twig', array('user' => $user, 'form' => $form->createView()));
    }
    private function recoverPass($id)
    {
        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery(
            'SELECT u.password
            FROM UserBundle:User u
            WHERE u.id = :id'    
        )->setParameter('id', $id);
        
        $currentPass = $query->getResult();
        
        return $currentPass;
    }

    public function viewAction($id)
    {
        
        $repository = $this->getDoctrine()->getRepository('UserBundle:User');
        
        $user = $repository->find($id);
        
        if(!$user)
        {
            throw $this->createNotFoundException('User not found.');
        }
       
        return $this->render('UserBundle:User:view.html.twig', array('user' => $user));
    }
  
    public function deleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        
        $user = $em->getRepository('UserBundle:User')->find($id);
        
        if(!$user)
        {
           throw $this->createNotFoundException('User not found.');
        }
           	
       	$em->remove($user);
       	$em->flush();

       	$this->addFlash('mensaje','¡Usuario eliminado exitosamente!');

       	return $this->redirectToRoute('user_index');    
    }
}
