<?php

namespace UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError; 
use UserBundle\Entity\Image;
use UserBundle\Form\ImageType;

class ImageController extends Controller
{
	public function addAction()
    {
        $image = new Image();
        $form = $this->createCreateForm($image);
        
        return $this->render('UserBundle:Image:add.html.twig', array('form' => $form->createView()));
    }

    private function createCreateForm(Image $entity)
    {
        $form = $this->createForm(new ImageType(), $entity, array(
            'action' => $this->generateUrl('image_create'),
            'method' => 'POST'
        ));
        
        return $form;
    }

    public function createAction(Request $request)
    {
        $image = new Image();
        $form = $this->createCreateForm($image);
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid())
        {
        	
        	$imgForm = $form->get('image')->getData();
            
            $imageConstraint = new Assert\NotBlank();
            $errorList = $this->get('validator')->validate($imgForm, $imageConstraint);
            
            if(count($errorList) == 0)
            {

	        	$userL = $this->container->get('security.context')->getToken()->getUser();
	            $image->setStatus(0);
	            $image->setUpdateAt(new \DateTime());
	            $foto=$image->getImage();
	            $fileName=$this->generateUniqueFileName().'.'.$foto->guessExtension();

	            $foto->move(
	           		$this->getParameter('imagesGallery_directory'),
	           		$fileName
	           );

	           $image->setImage($fileName);
	           $image->setUser($userL);

	           $em = $this->getDoctrine()->getManager();
	           $em->persist($image);
	      	   $em->flush();

	      	   $this->addFlash('mensaje', '¡Imágen guardada con éxito!');
	      	   return $this->redirectToRoute('image_index');
	      	}
	      	else{

	      		$errorMessage = new FormError($errorList[0]->getMessage());
                $form->get('image')->addError($errorMessage);
	      	}

        }
        return $this->render('UserBundle:Image:add.html.twig', array('form' => $form->createView()));     
    }

    /**
    * @return string
    **/
    private function generateUniqueFileName()
    {
    	return md5(uniqid());

    }

    public function indexAction()
    {
        
    	$userL = $this->container->get('security.context')->getToken()->getUser();
    	$userRole=$userL->getRole();
    	//var_dump($userRole);
    	$em= $this->getDoctrine()->getManager();
    	if ($userRole=='ROLE_ADMIN') {
    		$images= $em->getRepository('UserBundle:Image')->findAll();
    	}
    	elseif ($userRole=='ROLE_USER') {
    	
    		$userId=$userL->getId();
    		 $query = $em->createQuery(
	            'SELECT i.image,i.title,i.description,i.id
	            FROM UserBundle:Image i
	            WHERE i.user = :userid'    
	        )->setParameter('userid', $userL);
	        
	        $images = $query->getResult();
    	}
    	
    	return $this->render('UserBundle:Image:index.html.twig', array('images' => $images));
    }

    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $image = $em->getRepository('UserBundle:Image')->find($id);
        
        if(!$image)
        {
            throw $this->createNotFoundException('Image not found.');
        }
        
        $form = $this->createEditForm($image);
        
        return $this->render('UserBundle:Image:edit.html.twig', array('image' => $image, 'form' => $form->createView()));
        
    }
    private function createEditForm(Image $entity)
    {
        $form = $this->createForm(new ImageType(), $entity, array('action' => $this->generateUrl('image_update', array('id' => $entity->getId())), 'method' => 'PUT'));
        
        return $form;
    }

    public function updateAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        
        $image = $em->getRepository('UserBundle:Image')->find($id);

        if(!$image)
        {
            throw $this->createNotFoundException('Image not found.');
        }
        
        $form = $this->createEditForm($image);
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid())
        {
            $foto = $form->get('image')->getData();
            if(!empty($foto))
            {
                $image->setStatus(0);
	            
	            $fileName=$this->generateUniqueFileName().'.'.$foto->guessExtension();

	            $foto->move(
	           		$this->getParameter('imagesGallery_directory'),
	           		$fileName
	           );

	           $image->setImage($fileName);
	        }
            else
            {
                $recoverImg = $this->recoverImg($id);
                $image->setImage($recoverImg[0]['image']);                
            }
            
           
            $image->setUpdateAt(new \DateTime());

            $em->flush();
            
            $this->addFlash('mensaje', 'La información ha sido modificada correctamente.');
            return $this->redirectToRoute('image_index', array('id' => $image->getId()));
        }
        return $this->render('UserBundle:Image:edit.html.twig', array('image' => $image, 'form' => $form->createView()));
    }

    private function recoverImg($id)
    {
        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery(
            'SELECT i.image
            FROM UserBundle:Image i
            WHERE i.id = :id'    
        )->setParameter('id', $id);
        
        $currentPass = $query->getResult();
        
        return $currentPass;
    }

    public function deleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        
        $image = $em->getRepository('UserBundle:Image')->find($id);
        
        if(!$image)
        {
           throw $this->createNotFoundException('Image not found.');
        }
           	
       	$em->remove($image);
       	$em->flush();

       	$this->addFlash('mensaje','¡Imágen eliminada exitosamente!');

       	return $this->redirectToRoute('image_index');    
    }

    public function aproveAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        
        $image = $em->getRepository('UserBundle:Image')->find($id);
        
        if(!$image)
        {
           throw $this->createNotFoundException('Image not found.');
        }
           	
       	$image->setStatus(1);
       	$em->flush();

       	$this->addFlash('mensaje','¡Imágen aprobada exitosamente!');

       	return $this->redirectToRoute('image_index');    
    }
}
