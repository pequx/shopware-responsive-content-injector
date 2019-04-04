# PHAG Responsive Content Injector `1.0.9`

## Synopsis
The plugin provides a easy method to add responsive marketing content into a existing blog entry during edition in the visual backend interface.
It facilitates event subscribers, handles the blog content, transfer it into a node-like entity and returns enrhiched by auto-cached html snippets in accordance with the orginal parametric sequence.

## User Tests
The following list represents user tests needed to be passed before releasing plugin into production enviroment:

### Case 1 – Create a blog article with responsive contents	`BACKEND` `BLOG`
---
Using following paraphaps:
```
[ PAR 1 ] = Diesen Sommer heißt es „Mut zur Farbe“. Denn knallbunte Kleidungsstücke sind der absolute Renner bei stilbewussten Frauen. Egal ob zitronengelbes Top, grasgrüne Chinohose oder pinke Clutch - Damit werden Sie auf jeden Fall ein Blickfang sein. Und dieses Jahr gehen wir sogar noch einen Schritt weiter.

[ PAR 2 ] = Was zuvor ein absoluter Stilbruch war, ist jetzt Trend: Colour Blocking. Das Kombinieren von strahlenden und bunten Farben, die jeder Frau einen dynamischen und selbstbewussten Look verleihen. Und dabei können Sie kaum etwas falsch machen. Schauen Sie einfach, welche Farbe Ihrem Typ entspricht und in welcher Sie sich wohlfühlen. Den größten Effekt erzielen Sie durch konträre Farben mit der gleichen Intensität. Zudem sollten die Kleidungsstücke von der Form einfach und klassisch sein. Ansonsten sind Ihnen beim Colour Blocking keine Grenzen gesetzt. Je gewagter, desto besser. Und nach dem Trend können Sie einfach einzelne Kleidungsstücke als Basic weiternutzen.

[ TWITTER ] = Was zuvor ein absoluter Stilbruch war, ist jetzt Trend: Colour Blocking. Das Kombinieren von strahlenden und bunten Farben, die jeder Frau einen dynamischen und selbstbewussten Look verleihen. Und dabei können Sie kaum etwas falsch machen. Schauen Sie einfach #sugergeilyolo. Je gewagter, desto besser. Und nach dem Trend können Sie einfach einzelne Kleidungsstücke als Basic weiternutzen.

[ PICTURE 1 ] = Picture number 1 added from a Media Library. 
[ PICTURE 2 ] = Picture number 2 --"--
[ PICTURE 3 ] = Picture number 3 --"--
[ PICTURE 4 ] = Picture number 4 --"--
[ PICTURE 5 ] = Picture number 5 --"--
[ PICTURE 6 ] = Picture number 6 --"--
```
`TIP` Please consider using pictures in sequence from already defined category, for example: `BLOG` should contain some ordered pictures.

Due to a feature upgrade, all pictures now will be scaled into full width in the visual editor.

Tests the following section combinations (please note the strings no taken into brackets should be inputed as stated below):	
```
[ SET 1 ]

[ PAR 1 ]
## Bild
[ PICTURE 1 ]
[ PICTURE 2 ]
[ PICTURE 3 ]
# Layout 1
[ PAR 2 ]

-> SUCCESS

[ PAR 2 ]
## Bil
[ PICTURE 1 ]
[ PICTURE 2 ]
[ PICTURE 3 ]
# Layout 1
[ PAR 1 ]

-> FAILURE

[ PAR 1 ]
## Bi
[ PICTURE 1 ]
[ PICTURE 2 ]
[ PICTURE 3 ]
# Layout 1
[ PAR 2 ]

-> FAILURE

[ PAR 1 ]
## B
[ PICTURE 1 ]
[ PICTURE 2 ]
[ PICTURE 3 ]
# Layout 1
[ PAR 2 ]

-> FAILURE


[ SET 2 ]

[ PAR 1 ]
## Artikel
[ ARTICLE 1 ]
[ ARTICLE 2 ]
[ ARTICLE 3 ]
# Layout 1
[ PAR 2 ]

-> SUCCESS

[ PAR 1 ]
##Artikel
[ ARTICLE 1 ]
[ ARTICLE 2 ]
[ ARTICLE 3 ]
# Layout 1
[ PAR 2 ]

-> FAILURE

[ PAR 1 ]
## Artikel
[ ARTICLE 1 ]
[ ARTICLE 2 ]
[ ARTICLE 3 ]
# Layot 1
[ PAR 2 ]

-> FAILURE

[ PAR 1 ]
## Artikel
[ ARTICLE 1 ]
[ ARTICLE 2 ]
[ ARTICLE 3 ]
[ PAR 2 ]

-> FAILURE

[ SET 3 ]

[ PAR 1 ]
## B
[ PICTURE 1 ]
[ PICTURE 2 ]
[ PICTURE 3 ]
## L 1

-> SUCCESS (As it Doesn’t fill main pattern, ## -> #)

[ PAR 1 ]
# Artikel
[ ARTICLE 1 ]
[ ARTICLE 2 ]
[ ARTICLE 3 ]
# Layout 1

-> FAILURE

———————————
SET 4

[ PAR 1 ]
[ PICTURE 1 ]
[ PAR 2 ] 
[ PICTURE 2 ]
[ PAR 1 ] 
[ TWITTER ]
-> SUCCESS (only twitter hashtag).


Lorem ipsum dolor ## Artikel
-> FAILURE (as it fits into main pattern, and it’s not a twitter hashtag)


——————————=
SET 5 - MIXINS

[ PAR 1 ]
## Artikel
[ ARTICLE 1 ]
[ ARTICLE 2 ]
[ ARTICLE 3 ]
# Layout 1
[ PAR 2 ]

Lorem ipsum [ TWITTER ] 

[ PAR 1 ]
## Bi
[ PICTURE 1 ]
[ PICTURE 2 ]
[ PICTURE 3 ]
# Layout 1
[ PAR 2 ]

-> FAILURE (one section is ok, twitter is ok, but second section is wrong)

[ PAR 1 ]
## Artikel
[ ARTICLE 1 ]
[ ARTICLE 2 ]
[ ARTICLE 3 ]
# Layout 1
[ PAR 2 ]
[ TWITTER ] 

[ PAR 1 ]
## Bild
[ PICTURE 1 ]
[ PICTURE 2 ]
[ PICTURE 3 ]
# Layout 1
[ PAR 2 ]

-> SUCCESS

[ PAR 1 ]
## Bild
[ PICTURE 1 ]
https://upload.wikimedia.org/wikipedia/commons/3/3a/Cat03.jpg
[ PICTURE 3 ]
# Layout 1
[ PAR 2 ]

-> SUCCESS
```
(please note the link above should be provided as a string)