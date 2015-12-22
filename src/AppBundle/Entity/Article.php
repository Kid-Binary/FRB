<?php
// src/AppBundle/Entity/Article.php
namespace AppBundle\Entity;

use DateTime;

use IntlDateFormatter;

use Symfony\Component\HttpFoundation\File\File,
    Symfony\Component\Validator\Constraints as Assert;

use Doctrine\ORM\Mapping as ORM,
    Doctrine\Common\Collections\ArrayCollection;

use Gedmo\Mapping\Annotation as Gedmo,
    Gedmo\Translatable\Translatable;

use Vich\UploaderBundle\Mapping\Annotation as Vich;

use AppBundle\Entity\Utility\DoctrineMapping\IdMapper,
    AppBundle\Entity\Utility\DoctrineMapping\TranslationMapper;

/**
 * @ORM\Table(name="articles")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\Repository\ArticleRepository")
 *
 * @Gedmo\TranslationEntity(class="AppBundle\Entity\ArticleTranslation")
 *
 * @Vich\Uploadable
 */
class Article implements Translatable
{
    use IdMapper, TranslationMapper;

    const WEB_PATH        = "/uploads/articles/photos/";
    const WEB_PATH_THUMBS = "/uploads/articles/photos/thumbs/";

    const ARTICLES_PER_LIFT = 4;

    /**
     * @ORM\OneToMany(targetEntity="ArticleTranslation", mappedBy="object", cascade={"persist", "remove"})
     **/
    protected $translations;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Gedmo\Translatable
     **/
    protected $title;

    /**
     * @ORM\Column(type="text", nullable=false)
     */
    protected $content;

    /**
     * @ORM\Column(type="date", nullable=false)
     */
    protected $publicationDate;

    /**
     * @Assert\File(
     *     maxSize="2M",
     *     mimeTypes={"image/png", "image/jpeg", "image/pjpeg", "image/gif"}
     * )
     *
     * @Vich\UploadableField(mapping="article_photo", fileNameProperty="photoName")
     */
    protected $photoFile;

    /**
     * @ORM\Column(type="string", length=255)
     **/
    protected $photoName;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     **/
    protected $updatedAt;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->translations = new ArrayCollection;

        $this->setPublicationDate(new DateTime("NOW"));
    }

    /**
     * To string
     */
    public function __toString()
    {
        return ( $this->title ) ? $this->title : "";
    }

    /* Vich uploadable methods */

    public function setPhotoFile($photoFile = NULL)
    {
        $this->photoFile = $photoFile;

        if( $photoFile instanceof File )
            $this->updatedAt = new DateTime;
    }

    public function getPhotoFile()
    {
        return $this->photoFile;
    }

    public function getPhotoPath()
    {
        return ( $this->photoName )
            ? self::WEB_PATH.$this->photoName
            : FALSE;
    }

    public function getPhotoThumbPath()
    {
        return ( $this->photoName )
            ? self::WEB_PATH_THUMBS.$this->photoName
            : FALSE;
    }

    /* END Vich uploadable methods */

    /**
     * Set title
     *
     * @param string $title
     * @return Article
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return Article
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set publicationDate
     *
     * @param \DateTime $publicationDate
     * @return Article
     */
    public function setPublicationDate($publicationDate)
    {
        $this->publicationDate = $publicationDate;

        return $this;
    }

    /**
     * Get publicationDate
     *
     * @return \DateTime
     */
    public function getPublicationDate()
    {
        return $this->publicationDate;
    }

    /**
     * Set photoName
     *
     * @param string $photoName
     * @return Article
     */
    public function setPhotoName($photoName)
    {
        $this->photoName = $photoName;

        return $this;
    }

    /**
     * Get photoName
     *
     * @return string
     */
    public function getPhotoName()
    {
        return $this->photoName;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return Article
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    static public function getTransformedArticles(array $news, $isLast, $locale)
    {
        // Dirty hack
        $locale = ( $locale != 'ua' ) ?: 'uk';

        $articles = [];

        $formatter = IntlDateFormatter::create($locale, IntlDateFormatter::LONG, IntlDateFormatter::NONE);

        foreach($news as $article)
        {
            if( $article instanceof Article )
            {
                $articles[] = [
                    'photo' => $article->getPhotoName(),
                    'date' 	=> $formatter->format($article->getPublicationDate()),
                    'title'	=> $article->getTitle(),
                    'text' 	=> $article->getContent()
                ];
            }
        }

        $articles['isLast'] = $isLast;

        return $articles;
    }
}
