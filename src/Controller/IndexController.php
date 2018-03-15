<?php declare(strict_types=1);

namespace App\Controller;


use App\Package\Document\Converter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;

class IndexController extends Controller
{
    /**
     * @var Converter
     */
    private $converter;

    public function __construct(Converter $converter)
    {
        $this->converter = $converter;
    }

    /**
     * @return Response
     */
    public function index()
    {
        $form = $this->getFileForm()->createView();

        return $this->render('index/index.html.twig', compact('form'));
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function chooseColumns(Request $request)
    {
        $form = $this->getFileForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            /** @var UploadedFile $file */
            $file = $data['file'];

            $file = $file->move(
                $this->getParameter('storage.input.dir'),
                str_shuffle(md5(microtime()))
            );

            $path = $file->getRealPath();

            $columns = $this->converter->getColumns($path);

            $columnsForm = $this->getColumnsForm($file->getFilename(), $columns);
            $columnsForm->setData([
                'filename' => $file->getFilename(),
            ]);

            return $this->render('index/index.html.twig', [
                'form' => $columnsForm->createView()
            ]);
        }

        return $this->render('index/index.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @param Request $request
     * @param string $filename
     * @return Response
     */
    public function convert(Request $request, string $filename)
    {
        $columns = $this->converter->getColumns($this->getInputFilePath($filename));
        $form = $this->getColumnsForm($filename, $columns);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $content = $this->converter->xlsxToArff(
                $this->getInputFilePath($filename),
                $data['relation'],
                $data['columns']
            );

            return $this->arffResponse('kek', $content);
        }

        return $this->render('index/index.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @param string $name
     * @param string $content
     * @return Response
     */
    protected function arffResponse(string $name, string $content) : Response
    {
        $headers = [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => "attachment; filename={$name}.arff",
            'Expires' => 0,
            'Cache-Control' => 'must-revalidate',
            'Pragma' => 'public',
            'Content-Length' => strlen($content)
        ];
        return new Response($content, 200, $headers);
    }

    /**
     * @param string $filename
     * @return string
     */
    protected function getInputFilePath(string $filename = '')
    {
        return $this->getParameter('storage.input.dir') . '/' . $filename;
    }

    /**
     * @param string $filename
     * @param array $columns
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getColumnsForm(string $filename, array $columns)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('index.convert', ['filename' => $filename]))
            ->add('filename', HiddenType::class, [
                'constraints' => new Assert\NotBlank()
            ])
            ->add('relation', TextType::class, [
                'label' => 'Relation:',
                'constraints' => new Assert\NotBlank()
            ])
            ->add('columns', ChoiceType::class, [
                'multiple' => true,
                'attr' => ['size' => count($columns)],
                'choices' => array_flip($columns)
            ])
            ->add('submit', SubmitType::class)
            ->getForm();
    }

    /**
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getFileForm()
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('index.columns'))
            ->add('file', FileType::class, [
                'label' => 'File:',
                'constraints' => [
                    new Assert\File([
                        'mimeTypes' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
                    ])
                ]
            ])
            ->add('submit', SubmitType::class)
            ->getForm();
    }
}