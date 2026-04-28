<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Episode;
use App\Entity\Tag;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use League\CommonMark\CommonMarkConverter;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Fixtures Privaris, contenu réel prêt pour la mise en ligne.
 *
 * Édition recentrée « particuliers » au 22 avril 2026.
 * La ligne éditoriale : dédramatiser la cybersécurité, expliquer simplement,
 * donner des réflexes concrets à la maison, pour soi, ses proches, sa famille.
 * Le contenu des articles et descriptions de podcasts est rédigé en HTML
 * (rendu direct via {{ article.content|raw }}).
 */
class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        // ---- Admin par défaut (à CHANGER en prod via l'admin EasyAdmin) ----
        $admin = (new User())
            ->setEmail('admin@privaris.fr')
            ->setDisplayName('Arsène Cipher')
            ->setSlug('arsene-cipher')
            ->setBio('Fondateur et rédacteur de Privaris (pseudonyme). Couvre les arnaques en ligne, les comptes piratés et la protection des proches — pour un public qui n\'est pas dans la tech.')
            ->setRoles(['ROLE_SUPER_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'ChangeMe!2026'));
        $manager->persist($admin);

        // ---- Catégories (orientées particuliers / vie quotidienne) ----
        $categoriesData = [
            ['Actualités',        'actualites',        'L\'essentiel de la cyber du moment, traduit pour tout le monde.'],
            ['Décryptages',       'decryptages',       'On prend le temps d\'expliquer un sujet de fond, sans raccourci et sans jargon.'],
            ['Bonnes pratiques',  'bonnes-pratiques',  'Des réflexes simples à adopter aujourd\'hui pour protéger vos données.'],
            ['Alertes',           'alertes',           'Les arnaques et menaces du moment qu\'il vaut mieux connaître avant de tomber dedans.'],
            ['Famille & Maison',  'famille-maison',    'La cybersécurité à la maison : enfants, parents, Wi-Fi, objets connectés.'],
        ];
        $categories = [];
        foreach ($categoriesData as [$name, $slug, $desc]) {
            $c = (new Category())->setName($name)->setSlug($slug)->setDescription($desc);
            $manager->persist($c);
            $categories[$slug] = $c;
        }

        // ---- Tags (recentrés) ----
        $tagsData = [
            ['phishing',   'phishing'],
            ['arnaques',   'arnaques'],
            ['mdp',        'mots de passe'],
            ['mfa',        'double authentification'],
            ['wifi',       'Wi-Fi'],
            ['enfants',    'enfants'],
            ['ia',         'IA & deepfake'],
            ['smishing',   'SMS frauduleux'],
            ['piratage',   'piratage'],
            ['donnees',    'données personnelles'],
        ];
        $tags = [];
        foreach ($tagsData as [$slug, $name]) {
            $tag = (new Tag())->setName($name)->setSlug($slug);
            $manager->persist($tag);
            $tags[$slug] = $tag;
        }

        // ============================================================
        //  ARTICLES, chargés depuis privaris-editorial/articles/*.md
        //  Source unique de vérité pour le contenu éditorial.
        // ============================================================
        $this->loadArticlesFromMarkdown($manager, $admin, $categories, $tags);

        // ============================================================
        //  ÉPISODES DE PODCAST, scripts complets prêts à enregistrer
        //  Recentrés particuliers / dédramatisation.
        // ============================================================
        $episodeData = [
            // ---------- EP 1, Monologue ----------
            [
                'number'   => 1,
                'season'   => 'S1',
                'title'    => 'Le rançongiciel, c\'est quoi, en vrai ?',
                'slug'     => 'rancongiciel-cest-quoi',
                'excerpt'  => 'Un mot qu\'on entend partout, rarement expliqué simplement. Trois minutes pour comprendre ce qui se passe derrière, et pourquoi ça peut aussi toucher à la maison.',
                'days'     => 1,
                'duration' => '00:03:12',
                'size'     => 2_640_000,
                'format'   => 'Monologue',
                'description' => <<<'HTML'
<p><strong>Épisode 1 · Saison 1, Monologue · 3:12</strong></p>
<p>Le mot « rançongiciel », ou <em>ransomware</em> en anglais, est devenu le plus anxiogène de la cybersécurité. Dans ce premier épisode, on remet les choses à plat : comment ça fonctionne, pourquoi ça marche si bien pour les escrocs, et comment s'en prémunir simplement à la maison.</p>
<h3>Au programme</h3>
<ul>
    <li>Une définition accessible à tout le monde</li>
    <li>Les trois étapes d'une attaque type</li>
    <li>Le « double chantage » moderne, expliqué simplement</li>
    <li>Les quatre réflexes qui suffisent à la maison</li>
</ul>
HTML,
                'transcript' => <<<'TEXT'
[Bienvenue dans Privaris, le podcast qui explique la cybersécurité sans jargon. Ici Arsène Cipher, et aujourd'hui on parle d'un mot qu'on entend partout, sans toujours savoir ce qu'il veut dire. Rançongiciel.]

[Pause courte]

Rançongiciel. Ou ransomware, en anglais. Trois syllabes qui font peur. Mais dès qu'on comprend comment ça marche, c'est beaucoup moins effrayant. Et surtout, on sait quoi faire pour ne pas tomber dedans.

Alors voilà. Un rançongiciel, c'est un logiciel malveillant qui s'installe sur un appareil, ordinateur, téléphone, serveur d'entreprise, et qui chiffre tous les fichiers. Vos documents, vos photos, vos vidéos. Tout devient inaccessible. Et en même temps, un message s'affiche : « payez, si vous voulez récupérer vos données ».

[Pause]

Pour imaginer : c'est comme si quelqu'un entrait chez vous, mettait un cadenas sur tous vos placards, et vous demandait de payer pour avoir la clé. Vos affaires sont toujours là. Mais vous ne pouvez plus les toucher.

[Pause]

Et pourquoi c'est devenu si courant ? Parce que c'est devenu industriel. Les groupes qui font ça fonctionnent comme des entreprises, avec ceux qui écrivent le code, ceux qui le louent, et même ceux qui gèrent la « relation client » pendant la négociation. Oui, vraiment, avec des horaires et un service « support ». Ça donne une idée de l'échelle.

[Pause]

Alors comment est-ce que ça commence, concrètement ? Presque toujours de la même façon. Un email qui semble anodin. Une pièce jointe qu'on ouvre. Ou un lien sur lequel on clique. Ça installe en coulisses un petit programme, qui ouvre une porte discrète. Et pendant des jours, parfois des semaines, les escrocs explorent tranquillement le réseau, avant de déclencher l'attaque, presque toujours la nuit, souvent un vendredi, pour qu'on ne s'en aperçoive pas avant lundi.

Et pendant ce temps d'infiltration, ils font aussi autre chose. Ils copient un maximum de fichiers. Parce que la menace moderne, ce n'est plus juste « payez, ou vous perdez vos données ». C'est « payez, ou on publie tout sur internet ». Deux chantages en un.

[Pause]

Bon. Et nous, à la maison, qu'est-ce qu'on peut faire ?

Quatre choses. Pas plus.

Un. La double authentification, partout où c'est possible. Elle bloque 99 pour cent des attaques. C'est l'investissement le plus rentable de votre semaine.

Deux. Des sauvegardes. Vraies. Et au moins une copie débranchée du réseau, un disque dur qu'on rebranche une fois par mois, ou une clé USB qu'on sort de son tiroir. Une sauvegarde branchée en permanence se fait chiffrer comme le reste.

Trois. Les mises à jour. Je sais, c'est ennuyeux. Mais 60 pour cent des attaques exploitent des failles déjà corrigées depuis des mois. Laissez votre téléphone et votre ordinateur se mettre à jour tout seuls.

Quatre. Le doute avant le clic. Un email bizarre, un lien inattendu, trente secondes de pause, et on rappelle la personne ou on vérifie autrement. C'est la plus simple des protections, et celle qui marche le mieux.

[Pause]

Et si ça vous arrive malgré tout ? Ne payez pas. Allez sur cybermalveillance point gouv point fr, c'est gratuit, c'est sérieux, et ils vous aideront pas à pas.

[Pause courte]

Merci de m'avoir écouté. On se retrouve la semaine prochaine pour parler de phishing.

[Pause courte]

Ici Arsène Cipher. À mardi.

[Fin]
TEXT,
            ],

            // ---------- EP 2, Monologue ----------
            [
                'number'   => 2,
                'season'   => 'S1',
                'title'    => 'Le phishing en 3 minutes',
                'slug'     => 'phishing-3-minutes',
                'excerpt'  => '95% des piratages commencent par un simple email. On regarde ce qui fait mouche, et les quatre signes qui ne trompent jamais, même face à une arnaque bien écrite.',
                'days'     => 8,
                'duration' => '00:03:45',
                'size'     => 3_120_000,
                'format'   => 'Monologue',
                'description' => <<<'HTML'
<p><strong>Épisode 2 · Saison 1, Monologue · 3:45</strong></p>
<p>Le phishing, c'est la porte d'entrée numéro un des piratages. On explique en trois minutes comment ça marche, pourquoi ça marche, et les quatre signaux qui doivent déclencher votre vigilance, même quand l'email semble impeccable.</p>
<h3>Au programme</h3>
<ul>
    <li>D'où vient le mot « phishing »</li>
    <li>Pourquoi la forme n'est plus un indice fiable</li>
    <li>Les quatre signaux à connaître par cœur</li>
    <li>Le bon réflexe en cinq secondes</li>
</ul>
HTML,
                'transcript' => <<<'TEXT'
[Bienvenue dans Privaris. Ici Arsène Cipher. Aujourd'hui, trois minutes sur le phishing, un mot un peu jargonnant qui cache, au fond, quelque chose de très simple.]

[Pause courte]

Phishing. Le mot vient de l'anglais « fishing », la pêche. L'idée, c'est de jeter un hameçon dans l'eau et d'attendre de voir qui mord.

C'est exactement ça. Un escroc envoie un million d'emails ou de SMS. Il suffit que quelques dizaines de personnes cliquent pour que l'opération soit rentable. Et comme c'est quasi gratuit à envoyer, le calcul est vite fait.

[Pause]

Alors, à quoi ressemble un phishing en 2026 ? C'est justement là le problème. Avant, on disait « regardez les fautes d'orthographe ». Sauf que depuis l'arrivée des intelligences artificielles, les emails frauduleux sont écrits dans un français impeccable. Mieux, parfois, que les vraies entreprises.

Le logo est parfait. Le pied de page aussi. L'adresse de l'expéditeur est presque la bonne. Presque.

Du coup, si on ne peut plus se fier à la forme, on se fie à quoi ?

Quatre signaux. Quatre, pas plus.

[Pause courte]

Premier signal : l'urgence. « Votre compte sera suspendu dans 24 heures ». « Payez maintenant ou votre livraison sera annulée ». Une banque, une administration, un service sérieux, ça ne marche jamais comme ça. L'urgence est un levier pour vous faire cliquer avant de réfléchir. Si vous la sentez, ralentissez. C'est souvent là que le piège se déclenche.

Deuxième signal : la demande d'informations sensibles. Mot de passe. Code de carte. Numéro de sécurité sociale. Jamais, au grand jamais, une administration ou une banque légitime ne vous demandera ça par email ou par SMS. Zéro exception.

Troisième signal : l'adresse du site. Survolez le lien sans cliquer, sur ordinateur, ça montre la vraie destination en bas de l'écran. Sur téléphone, appuyez longuement sans relâcher, ça affiche l'URL. Un site officiel français finit toujours par point gouv point fr. Si c'est « impots-gouv point fr » avec un tiret, ou « ameli-service point com », ce n'est pas le vrai.

Quatrième signal, le plus important, le contexte. Est-ce que j'attendais ce message ? De cette personne ? Sur ce sujet ? Si la réponse est non, doutez. Même si tout le reste a l'air parfait.

[Pause]

Et le bon réflexe si vous hésitez ? Simple. Ne cliquez pas sur le lien du mail. Ouvrez un nouvel onglet. Tapez vous-même l'adresse du site officiel. Si l'info était vraie, elle sera visible dans votre espace personnel. Si elle n'y est pas, vous avez votre réponse.

Trente secondes. C'est le coût d'un doute raisonnable.

[Pause]

Dernière chose. Si vous avez cliqué et saisi des informations : pas de panique, mais agissez vite. Changez votre mot de passe. Appelez votre banque si vous avez donné votre carte. Signalez le mail sur signal-spam point fr. Et si vous êtes perdu, cybermalveillance point gouv point fr vous accompagne gratuitement.

[Pause courte]

Le phishing, ce n'est pas une fatalité. C'est un réflexe à prendre. Trente secondes, quatre questions, et on désamorce la quasi-totalité des pièges.

La semaine prochaine sur Privaris, on parlera de la double authentification, pourquoi c'est probablement la chose la plus rentable que vous ferez cette année.

[Pause courte]

Ici Arsène Cipher. À mardi.

[Fin]
TEXT,
            ],

            // ---------- EP 3, Monologue ----------
            [
                'number'   => 3,
                'season'   => 'S1',
                'title'    => 'Pourquoi la double authentification vaut toutes les précautions',
                'slug'     => 'pourquoi-2fa',
                'excerpt'  => 'Selon Microsoft, elle bloque plus de 99% des tentatives de piratage. En trois minutes, on comprend pourquoi, et comment l\'activer en deux minutes chrono.',
                'days'     => 15,
                'duration' => '00:03:30',
                'size'     => 2_880_000,
                'format'   => 'Monologue',
                'description' => <<<'HTML'
<p><strong>Épisode 3 · Saison 1, Monologue · 3:30</strong></p>
<p>C'est probablement la mesure la plus efficace qui existe en cybersécurité pour un particulier. Deux minutes pour l'activer, un quotidien presque inchangé, et 99% des tentatives de piratage qui tombent. Dans cet épisode, on explique pourquoi, et comment s'y mettre ce soir.</p>
HTML,
                'transcript' => <<<'TEXT'
[Vous écoutez Privaris, ici Arsène Cipher. Aujourd'hui, on parle de la mesure de sécurité la plus efficace que vous pouvez prendre ce soir. Deux minutes d'installation. Effet massif.]

[Pause]

La double authentification, parfois appelée 2FA, ou MFA, selon comment on l'abrège, ça veut dire : un mot de passe ne suffit plus. On ajoute une deuxième clé.

Pourquoi ? Parce qu'un mot de passe, ça se vole. Par un phishing, par une fuite sur un site qu'on utilise, ou par une attaque sur un service dont on ne se souvient même plus s'être inscrit. Des milliards de mots de passe circulent sur internet à ce moment précis. Le vôtre est probablement dans le tas, au moins partiellement.

[Pause]

Alors la double authentification, elle ajoute un deuxième élément. Après votre mot de passe, le service vous demande un petit code à six chiffres. Ce code, il change toutes les 30 secondes, et il s'affiche dans une application sur votre téléphone.

Résultat : même si un escroc a votre mot de passe, ce qu'il vaut mieux considérer comme possible, aujourd'hui, il ne peut rien en faire. Il lui manque votre téléphone. Et voler votre téléphone, ce n'est plus une attaque à distance. C'est un cambriolage. C'est exponentiellement plus rare.

[Pause]

Combien ça change, dans les faits ? Microsoft a publié la statistique, parce qu'ils ont quelques milliards de comptes à surveiller. Avec un mot de passe seul : des millions d'attaques réussies par jour. Avec la double authentification activée : plus de 99 pour cent des attaques sont bloquées. 99 pour cent.

Aucune autre mesure de cybersécurité n'a ce rapport effort-résultat.

[Pause]

Alors, comment on l'active ? Trois étapes.

Un. Vous installez une application d'authentification. Moi, je recommande Aegis, qui est open source. Sinon Google Authenticator, Microsoft Authenticator, les trois marchent très bien.

Deux. Vous allez dans les paramètres de sécurité de votre compte. Votre boîte mail en premier. Parce que votre mail, c'est la clé qui ouvre tous les autres. Puis votre banque. Puis vos comptes importants.

Trois. Vous scannez le QR code affiché par le site, avec l'application. Elle génère alors un code à six chiffres. Vous le tapez. C'est activé.

Dernière étape, et celle-là on l'oublie souvent : notez les codes de récupération. Le site vous en fournit généralement six ou dix. Imprimez-les, rangez-les dans un tiroir ou dans un coffre-fort papier. Si vous perdez votre téléphone un jour, ce sont eux qui vous sauveront.

[Pause]

Petite nuance importante. Certains sites proposent la double authentification par SMS. C'est mieux que rien. Mais c'est fragile : les SMS peuvent être interceptés. Donc si l'option « application » existe, prenez-la. Elle est plus sûre.

[Pause]

Pour récapituler. Deux minutes d'installation. Une application sur votre téléphone. Le mail, la banque, vos comptes importants. Et vous passez dans le camp des 1 pour cent qui restent difficiles à pirater, au lieu des 99 qui tombent.

Faites-le ce soir. Pas demain. C'est sans doute la chose la plus rentable que vous ferez cette semaine pour votre sécurité numérique. Et accessoirement, c'est gratuit.

La semaine prochaine, sur Privaris, on reçoit une invitée pour parler d'un sujet qui inquiète beaucoup de parents : comment protéger ses enfants en ligne, sans dramatiser.

[Pause courte]

Ici Arsène Cipher. À mardi.

[Fin]
TEXT,
            ],

            // ---------- EP 4, Dialogue (REMPLACE RGPD) ----------
            [
                'number'   => 4,
                'season'   => 'S1',
                'title'    => 'Protéger ses enfants en ligne : conversation avec une éducatrice',
                'slug'     => 'enfants-en-ligne-educatrice',
                'excerpt'  => 'Les vraies questions que se posent les parents, les réponses d\'une éducatrice spécialisée qui accompagne des familles au quotidien. Sans jargon, sans alarmisme, avec des pistes concrètes.',
                'days'     => 22,
                'duration' => '00:04:20',
                'size'     => 3_600_000,
                'format'   => 'Dialogue',
                'description' => <<<'HTML'
<p><strong>Épisode 4 · Saison 1, Dialogue · 4:20</strong></p>
<p>Quel âge pour le premier téléphone ? Faut-il contrôler ou faire confiance ? Que faire si mon enfant est tombé sur un contenu choquant ? Dans cet épisode, Arsène Cipher reçoit Claire Martin, éducatrice spécialisée en accompagnement numérique des familles. Quatre minutes pour dédramatiser, et quelques pistes très concrètes pour aborder le sujet à la maison.</p>
<h3>Au programme</h3>
<ul>
    <li>Le « bon » âge pour le premier téléphone</li>
    <li>Contrôle parental : ami ou illusion ?</li>
    <li>Que faire face à un contenu choquant ou un harcèlement</li>
    <li>Les conversations qui comptent vraiment</li>
</ul>
HTML,
                'transcript' => <<<'TEXT'
[Vous écoutez Privaris, ici Arsène Cipher. Aujourd'hui, format un peu différent : un dialogue. J'ai le plaisir d'accueillir Claire Martin, éducatrice spécialisée. Claire accompagne des familles sur les questions numériques depuis huit ans. Claire, bonjour.]

, Bonjour Arsène. Ravie d'être là. Claire, première question qu'on me pose le plus souvent, et je te la passe : quel est le bon âge pour le premier téléphone ?

, La question qu'on me pose tout le temps. Et ma réponse, c'est qu'il n'y a pas d'âge magique, il y a un moment. Le moment où l'enfant en a vraiment besoin, pour rentrer seul de l'école, par exemple. Et le moment où il est prêt à ne pas être entièrement absorbé par l'outil. Chez la plupart des familles que je vois, ça se joue vers l'entrée au collège, 11-12 ans. Mais je connais des enfants de 10 ans à qui ça va très bien, et des ados de 14 qui n'y sont pas prêts. C'est individuel. Et le premier téléphone, tu recommandes smartphone ou téléphone basique ?

, Basique, souvent plus longtemps qu'on ne pense. Un téléphone qui sert à appeler et à envoyer des SMS, pendant quelques mois, ça permet à l'enfant d'apprendre la notion de responsabilité sans se prendre d'un coup tout l'océan d'internet dans la figure. Ensuite on bascule, progressivement. Deuxième question, le contrôle parental, ami ou illusion ?

, Les deux. Pour les jeunes enfants, jusqu'à 10-11 ans, ça a vraiment un sens. Ça évite des chocs inutiles, pornographie, violence, en filtrant l'accès au niveau du Wi-Fi de la maison ou du téléphone. C'est un filet.

Mais avec les adolescents, je vois souvent la même chose : les parents investissent dans un outil cher, et l'ado le contourne en trois jours. Ce n'est pas par malice, c'est par créativité, ils se renseignent entre eux. Alors à partir du collège, ce qui marche vraiment, c'est la conversation, pas la surveillance. Tu veux dire quoi par « conversation » ?

, Par exemple : au lieu d'interdire TikTok, on passe un soir à regarder ensemble dix vidéos. On commente. On remarque que tel créateur paraît cool mais passe son temps à vendre. On voit comment l'algorithme amène à autre chose de plus inquiétant. L'ado apprend à décoder, en compagnie d'un adulte qui ne le juge pas. C'est infiniment plus durable qu'un blocage qui durera trois semaines. Un sujet qui revient beaucoup : les contenus choquants. Un enfant qui tombe, sans chercher, sur une vidéo pornographique, ou violente, ou haineuse. Qu'est-ce qu'on fait ?

, D'abord, on respire. L'enfant n'est pas cassé parce qu'il a vu. Ce qui compte, c'est ce qui suit. Et la phrase la plus importante à dire est : « merci d'être venu me voir ». Même si on est gêné. Même si on ne sait pas quoi dire. Et ensuite ?

, Ensuite, on en parle calmement, au niveau de ce que l'enfant peut entendre. On dit que ce qu'il a vu n'est pas la réalité, ni la vraie vie, ni la vraie sexualité, ni le vrai monde. On vérifie comment il se sent. Et on ajuste le niveau de filtrage sur les appareils, sans en faire une punition. Un mot sur le harcèlement en ligne ?

, C'est devenu un des motifs majeurs pour lesquels on me contacte. Les enfants sous-estiment ce qui se passe dans les groupes WhatsApp, sur Snapchat, sur les stories Instagram. Je voudrais qu'ils retiennent trois choses. Un : si ça te fait mal, c'est grave, même si « on a juste plaisanté ». Deux : les captures d'écran sont des preuves. Garde-les. Trois : le 3018, trois zéro un huit, est un numéro gratuit, anonyme, avec des professionnels spécialisés. Les parents peuvent appeler aussi. Dernière question, ce que tu aimerais que tous les parents fassent, si on ne devait retenir qu'une chose. Une conversation par mois, au moins. Vraiment. Pas pour « faire le point sur ton téléphone ». Juste pour demander « qu'est-ce que tu regardes en ce moment, qui te plaît, qu'est-ce qui te saoule, est-ce que tu as vu des trucs bizarres ? ». Sans jugement. Sans sanction si la réponse est compliquée. Ce rendez-vous régulier vaut dix logiciels de contrôle. Claire, merci beaucoup. Merci à toi Arsène. Chers auditeurs, on retrouve en description de l'épisode les liens vers le 3018 et vers notre article sur les sept conversations à avoir avec ses enfants.

[Pause courte]

Ici Arsène Cipher. À mardi.

[Fin]
TEXT,
            ],

            // ---------- EP 5, Dialogue (REMPLACE IA PRO) ----------
            [
                'number'   => 5,
                'season'   => 'S1',
                'title'    => 'Deepfakes au téléphone : conversation avec un ancien enquêteur',
                'slug'     => 'deepfakes-telephone-enqueteur',
                'excerpt'  => 'Comment les arnaques vocales par IA arrivent dans le quotidien des familles françaises, et les trois réflexes qui coupent 99% de ces pièges.',
                'days'     => 1,
                'duration' => '00:03:50',
                'size'     => 3_120_000,
                'format'   => 'Dialogue',
                'description' => <<<'HTML'
<p><strong>Épisode 5 · Saison 1, Dialogue · 3:50</strong></p>
<p>« Mamie, j'ai eu un accident, envoie-moi 2 000 euros ». La voix est celle de votre petit-fils. Sauf que ce n'est pas lui. Arsène Cipher reçoit Thomas Leclerc, ancien enquêteur en cybercriminalité passé dans le conseil aux particuliers. Trois vérités terrain, et surtout trois réflexes à installer en famille, sans dramatisation.</p>
<h3>Au programme</h3>
<ul>
    <li>Les arnaques à la voix qui circulent en France</li>
    <li>Pourquoi les personnes âgées sont visées en premier</li>
    <li>Le « mot de confiance » familial</li>
    <li>Un réflexe à ancrer dès demain</li>
</ul>
HTML,
                'transcript' => <<<'TEXT'
[Bienvenue dans Privaris. Ici Arsène Cipher. Aujourd'hui, format dialogue, sujet qu'on m'a demandé plusieurs fois : les arnaques à la voix. Celles qui utilisent l'intelligence artificielle pour imiter un proche. J'ai avec moi Thomas Leclerc, ancien enquêteur en cybercriminalité, qui accompagne aujourd'hui des particuliers et des familles. Thomas, bonjour.]

, Bonjour Arsène. Merci pour l'invitation. Thomas, première chose : est-ce qu'on peut commencer par dire à quel point c'est devenu accessible ? Parce que beaucoup pensent encore que c'est de la science-fiction. C'est plus accessible qu'on ne l'imagine. Aujourd'hui, avec 30 secondes d'une voix disponible en ligne, un vocal posté sur un réseau social, une vidéo d'anniversaire partagée, un message d'anniversaire laissé sur un répondeur, on peut imiter cette voix de façon très convaincante. En quelques minutes. Avec des outils qu'on trouve publiquement. Donc le « tonton qui a envoyé une vidéo à l'anniversaire », ça suffit. Ça peut suffire, oui. Et j'insiste, je ne dis pas ça pour faire peur. Je dis ça pour qu'on prenne au sérieux les deux ou trois réflexes dont on va parler. Tu as vu des cas concrets en France ?

, Beaucoup. Celui qui revient le plus, on l'appelle « l'arnaque au petit-fils ». Une personne âgée reçoit un appel. Son petit-fils, en pleurs. « Mamie, j'ai eu un accident, je suis au poste, il me faut deux mille euros pour la caution, ne dis rien à maman ». La voix est juste. L'émotion aussi. Et les sommes vont de quelques centaines à plusieurs dizaines de milliers d'euros. Et les personnes âgées sont visées spécifiquement ?

, Oui. Et pas parce qu'elles sont « moins intelligentes », mais parce qu'elles font confiance à la voix. Les générations plus jeunes ont grandi avec le doute numérique par défaut. Quelqu'un qui a passé 70 ans à faire confiance à ce qu'il entend au téléphone, c'est un autre référentiel. L'arnaqueur le sait, et il l'exploite. Alors qu'est-ce qu'on fait ? Donne-nous trois réflexes concrets. Le premier, c'est le plus simple. Si quelqu'un, même un proche, demande de l'argent au téléphone avec urgence : on dit « je te rappelle dans cinq minutes », on raccroche, et on rappelle <em>soi-même</em> au numéro qu'on a dans ses contacts. Pas le numéro qui vient d'appeler. Le vrai numéro, celui qu'on a depuis longtemps. Si c'était le vrai petit-fils, il répondra et vous rirez ensemble. Si c'était une arnaque, elle se dissoudra. Trente secondes, donc. Trente secondes qui valent dix mille euros, dans certains cas que j'ai traités. Deuxième réflexe ?

, Le mot de confiance. Vous choisissez, en famille, un mot ou une phrase banale, une ville, un prénom de chat, une couleur, « glycine au portail », ce que vous voulez, que seuls les membres de la famille connaissent. Et vous convenez : en cas d'urgence au téléphone, on demande ce mot avant d'agir. Ça paraît enfantin, ça l'est un peu. Mais aucun escroc ne peut deviner « glycine au portail ». Et ça désarme instantanément toutes les arnaques à la voix. C'est mignon, et c'est efficace. C'est ça. Les enfants adorent. Les grands-parents aussi, parce que ça leur donne une ligne de défense claire, sans qu'ils aient à devenir experts en intelligence artificielle. Troisième ?

, Parler de tout ça maintenant. Vraiment. Prendre vingt minutes, à la prochaine réunion de famille, pour dire à ses parents et à ses grands-parents : voilà ce qui arrive aux gens aujourd'hui, et voilà nos deux règles maison. On met en place le mot de confiance. Et on adopte le « je te rappelle dans cinq minutes ». C'est un investissement dérisoire en temps, pour une vraie tranquillité. Un dernier mot pour les gens qui écoutent ?

, Juste ça : ces arnaques sont conçues pour tromper même les gens vigilants. Tomber dedans, ce n'est pas être bête. C'est être surpris. Donc si un proche se fait avoir, on n'en fait pas une histoire de jugement. On en parle, on aide, on appelle cybermalveillance point gouv point fr. Et on en sort plus solide en famille. Thomas, merci. Merci Arsène. Chers auditeurs, si vous voulez approfondir, notre article « Deepfakes et arnaques vocales » est lié en description.

[Pause courte]

Ici Arsène Cipher. À mardi.

[Fin]
TEXT,
            ],
        ];

        // Les 5 scripts d'épisodes existent comme BROUILLONS en base (statut DRAFT,
        // donc invisibles publiquement). Quand Will enregistre un épisode, il
        // remplace l'audioUrl placeholder par la vraie URL et passe en PUBLISHED
        // depuis EasyAdmin. La page /podcast affiche un empty state élégant tant
        // qu'aucun épisode n'est publié.
        // Pour réactiver la publication automatique : remplacer Episode::STATUS_DRAFT
        // par Episode::STATUS_PUBLISHED ci-dessous.
        foreach ($episodeData as $d) {
            $e = (new Episode())
                ->setNumber($d['number'])
                ->setSeason($d['season'])
                ->setTitle($d['title'])
                ->setSlug($d['slug'])
                ->setExcerpt($d['excerpt'])
                ->setDescription($d['description'])
                ->setTranscript($d['transcript'])
                ->setAudioUrl('https://cdn.privaris.fr/audio/'.$d['slug'].'.mp3')
                ->setAudioSizeBytes($d['size'])
                ->setDuration($d['duration'])
                ->setAudioMimeType('audio/mpeg')
                ->setStatus(Episode::STATUS_DRAFT)
                ->setPublishedAt(new \DateTimeImmutable('-'.$d['days'].' days'))
                ->setEpisodeType('full');
            $manager->persist($e);
        }

        $manager->flush();
    }

    /**
     * Charge tous les articles depuis ../../../privaris-editorial/articles/*.md
     * Frontmatter YAML attendu : slug, category, tags, requete_cible (facultatif),
     * date_publication (facultatif), derniere_maj (facultatif), mots (facultatif),
     * titre_seo (facultatif), meta_description (facultatif),
     * alert (bool, facultatif), featured (bool, facultatif).
     * Titre extrait du premier H1. Excerpt extrait du premier paragraphe substantiel.
     */
    private function loadArticlesFromMarkdown(
        ObjectManager $manager,
        User $admin,
        array $categories,
        array $tags,
    ): void {
        $dir = \dirname(__DIR__, 3) . '/privaris-editorial/articles';
        if (!is_dir($dir)) {
            // Fallback : dossier éditorial au même niveau que privaris-web
            $dir = \dirname(__DIR__, 4) . '/privaris-editorial/articles';
        }
        if (!is_dir($dir)) {
            throw new \RuntimeException('Dossier éditorial introuvable : ' . $dir);
        }

        $files = glob($dir . '/*.md');
        sort($files);

        $converter = new CommonMarkConverter([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);

        foreach ($files as $file) {
            $raw = file_get_contents($file);
            if ($raw === false) {
                continue;
            }

            // Sépare frontmatter et corps
            if (!preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $raw, $m)) {
                // Pas de frontmatter : on ignore
                continue;
            }
            $meta = Yaml::parse($m[1]) ?: [];
            $body = $m[2];

            // Titre = premier H1
            $title = null;
            if (preg_match('/^#\s+(.+)$/m', $body, $hm)) {
                $title = trim($hm[1]);
            }
            if (!$title) {
                // Fallback sur h1 explicite du frontmatter
                $title = $meta['h1'] ?? $meta['titre_seo'] ?? basename($file, '.md');
            }
            // Limite Article : 200 caractères
            $title = mb_substr($title, 0, 200);

            // Excerpt = premier paragraphe substantiel après H1, hors italique meta
            $excerpt = $this->extractExcerpt($body);

            // SEO
            $seoTitle = $meta['titre_seo'] ?? $title;
            $seoTitle = mb_substr($seoTitle, 0, 160);
            $seoDesc = $meta['meta_description'] ?? $excerpt;
            $seoDesc = mb_substr($seoDesc, 0, 300);

            // Slug
            $slug = $meta['slug'] ?? basename($file, '.md');
            $slug = preg_replace('/^\d+-/', '', $slug); // retire un prefixe numerique éventuel
            $slug = mb_substr($slug, 0, 220);

            // Category
            $categorySlug = $meta['category'] ?? null;
            if (!$categorySlug || !isset($categories[$categorySlug])) {
                throw new \RuntimeException(sprintf(
                    'Catégorie "%s" inconnue pour %s (valeurs attendues : %s)',
                    (string) $categorySlug,
                    basename($file),
                    implode(', ', array_keys($categories)),
                ));
            }

            // Date de publication : date_publication si fournie, sinon maintenant
            $publishedAt = new \DateTimeImmutable('now');
            if (!empty($meta['date_publication'])) {
                try {
                    $publishedAt = new \DateTimeImmutable((string) $meta['date_publication']);
                } catch (\Exception) {
                    // garde maintenant
                }
            }

            // Statut : published si date_publication <= maintenant, sinon scheduled
            $status = $publishedAt > new \DateTimeImmutable('now')
                ? Article::STATUS_SCHEDULED
                : Article::STATUS_PUBLISHED;

            // Minutes de lecture : champ mots / 200 si disponible
            $reading = null;
            if (isset($meta['mots'])) {
                $reading = max(1, (int) ceil(((int) $meta['mots']) / 200));
            } else {
                $wordCount = str_word_count(strip_tags($body));
                $reading = max(1, (int) ceil($wordCount / 200));
            }

            // Conversion markdown -> HTML
            $content = (string) $converter->convert($body);

            $article = (new Article())
                ->setTitle($title)
                ->setSlug($slug)
                ->setExcerpt(mb_substr($excerpt, 0, 280))
                ->setSeoTitle($seoTitle)
                ->setSeoDescription($seoDesc)
                ->setContent($content)
                ->setStatus($status)
                ->setPublishedAt($publishedAt)
                ->setReadingMinutes($reading)
                ->setFeatured((bool) ($meta['featured'] ?? false))
                ->setAlert((bool) ($meta['alert'] ?? false))
                ->setAuthor($admin)
                ->setCategory($categories[$categorySlug]);

            foreach ((array) ($meta['tags'] ?? []) as $tagSlug) {
                if (isset($tags[$tagSlug])) {
                    $article->addTag($tags[$tagSlug]);
                }
            }

            $manager->persist($article);
        }
    }

    /**
     * Extrait le premier paragraphe substantiel du corps markdown,
     * en ignorant le H1 et les lignes d'italique « meta » (ex: *Mis à jour le...*).
     */
    private function extractExcerpt(string $body): string
    {
        $lines = preg_split('/\R/', $body);
        $h1Seen = false;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (!$h1Seen && str_starts_with($line, '# ')) {
                $h1Seen = true;
                continue;
            }
            if (!$h1Seen) {
                continue;
            }
            if (str_starts_with($line, '#')) {
                continue; // sous-titre
            }
            // Ligne d'italique seule (ex : *Mis à jour le 23 avril 2026.*)
            if (preg_match('/^\*[^*].*[^*]\*$/', $line) && !preg_match('/\*\*/', $line)) {
                continue;
            }
            // Strip bold/italique wrapping pour l'excerpt
            $clean = $line;
            $clean = preg_replace('/\*{1,2}([^*]+)\*{1,2}/', '$1', $clean);
            // Supprime liens markdown [texte](url) -> texte
            $clean = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $clean);
            // Supprime code inline
            $clean = preg_replace('/`([^`]+)`/', '$1', $clean);
            $clean = trim((string) $clean);
            if ($clean === '') {
                continue;
            }
            if (mb_strlen($clean) > 275) {
                $clean = mb_substr($clean, 0, 275) . '…';
            }
            return $clean;
        }
        return '';
    }
}
