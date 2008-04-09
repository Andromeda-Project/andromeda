<?php
    Class AndroPlugin_rssReader extends AndroPlugin {

            function __construct() {
                $this->setType( 'UI' );
            }

            function getFeed( $args ) {
                $this->parseArgs( $args );
                $xml = file_get_contents( $this->feed );

                $xmlObj = @new SimpleXMLElement( $xml );
                if ( $xmlObj ) {
                        $articles = array();
                        foreach( $xmlObj->item as $art ) {
                            $article = array();
                            $article['title'] = (string)$art->title;
                            $article['link'] = (string)$art->link;
                            $article['description'] = (string)$art->description;
                            $article['pubDate'] = (string)$art->pubDate;
                            array_push( $articles, $article );
                        }
                        return $articles;
                } else {
                        return false;
                }
            }

    }
?>
