<?php
/**
 * This file is part of the Clastic package.
 *
 * (c) Dries De Peuter <dries@nousefreak.be>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Clastic\BackofficeBundle\Controller;

use Clastic\CoreBundle\Entity\Node;
use Clastic\BackofficeBundle\Form\NodeType;
use Clastic\CoreBundle\Module\ModuleManager;
use Clastic\TextBundle\Entity\Text;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * NodeController
 *
 * @author Dries De Peuter <dries@nousefreak.be>
 */
class NodeController extends Controller
{
    public function listAction($type)
    {
        $queryBuilder = $this->getDoctrine()
            ->getManager()
            ->createQueryBuilder()
            ->select('e')
            ->from('ClasticTextBundle:Text', 'e')
            ->orderBy('e.id', 'DESC')
        ;

        $adapter = new DoctrineORMAdapter($queryBuilder);
        $data = new Pagerfanta($adapter);

        return $this->render('ClasticBackofficeBundle:Node:list.html.twig', array(
            'data' => $data,
            'type' => $type,
        ));
    }

    public function formAction($type, $node_id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        if (!is_null($node_id)) {
            $data = $em->getRepository('ClasticTextBundle:Text')->find($node_id);
        } else {
            $data = new Text();

            if (!$data->getNode()) {
                $node = new Node();
                $node->setType($type);
                $node->setUserId(1);
                $node->setCreated(new \DateTime());

                $data->setNode($node);
            }
        }

        $form = $this->createForm(new NodeType(), $data);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $node = $data->getNode();

            $node->setChanged(new \DateTime());

            $em->persist($data);
            $em->flush();

            $request->getSession()->getFlashBag()->add(
                'success',
                'Your changes were saved!'
            );

            return $this->redirect($this->generateUrl('clastic_backoffice_form', array(
                 'type' => $type,
                 'node_id' => $data->getNode()->getId(),
            )));
        }


        return $this->render('ClasticBackofficeBundle:Node:form.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @return ModuleManager
     */
    private function getModuleManager()
    {
        return $this->get('clastic.module_manager');
    }
}
