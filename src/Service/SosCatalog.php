<?php

namespace App\Service;

/**
 * Catalogue des scénarios SOS — un guide d'action pour les personnes en panique.
 *
 * Le contenu est en code pour l'instant. Migration vers une entité Doctrine
 * (avec édition via EasyAdmin) prévue dans une prochaine itération, dès que
 * le ton éditorial est stabilisé.
 *
 * Le ton à respecter ici :
 *   - On parle à quelqu'un qui n'est PAS dans la tech.
 *   - Pas de sigle non expliqué (jamais « 2FA » sans dire ce que c'est).
 *   - Pas de mot anglais sans glose (« phishing » devient « hameçonnage »).
 *   - On adresse la personne en « vous », on dit « on » pour le collectif.
 *   - On reconnaît l'émotion quand c'est utile (« c'est désagréable, c'est normal »).
 *   - On vient d'un côté concret : « ouvrez votre boîte mail », pas
 *     « accédez à votre messagerie ».
 *
 * Structure d'un scénario : voir le code ci-dessous.
 */
final class SosCatalog
{
    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return [
            $this->scenarioComptePirate(),
            $this->scenarioPhishing(),
            $this->scenarioVirus(),
            $this->scenarioFuite(),
            $this->scenarioSextorsion(),
        ];
    }

    /** @return array<string, mixed>|null */
    public function findBySlug(string $slug): ?array
    {
        foreach ($this->all() as $scenario) {
            if ($scenario['slug'] === $slug) {
                return $scenario;
            }
        }

        return null;
    }

    /**
     * Renvoie les scénarios listés dans `related` d'un scénario donné.
     *
     * @return array<int, array<string, mixed>>
     */
    public function relatedTo(array $scenario): array
    {
        $relatedSlugs = $scenario['related'] ?? [];
        $related = [];
        foreach ($relatedSlugs as $slug) {
            $r = $this->findBySlug($slug);
            if ($r !== null) {
                $related[] = $r;
            }
        }

        return $related;
    }

    /** @return array<string, mixed> */
    private function scenarioComptePirate(): array
    {
        return [
            'slug'    => 'compte-pirate',
            'title'   => 'Mon compte a été piraté',
            'kicker'  => 'Boîte mail · Réseau social · Banque',
            'summary' => 'Votre mot de passe ne marche plus, vos amis reçoivent des messages bizarres de votre part, ou vous voyez une connexion que vous n\'avez pas faite. Quelqu\'un est entré dans votre compte. On va le mettre dehors et reverrouiller la porte.',
            'urgency' => 'urgent',
            'diagnosis' => [
                'Votre mot de passe ne fonctionne plus, alors que vous ne l\'avez pas changé.',
                'Vos proches vous appellent : ils ont reçu un message bizarre venant de vous.',
                'Vous voyez dans votre boîte mail des messages envoyés que vous ne reconnaissez pas.',
                'Vous recevez une notification du genre « Nouvelle connexion depuis Istanbul ».',
                'Votre relevé bancaire montre un achat que vous n\'avez pas fait.',
            ],
            'steps' => [
                [
                    'key' => 'immediate',
                    'label' => 'Dans les 10 prochaines minutes',
                    'note'  => 'On va vite, on ne réfléchit pas trop. L\'idée : virer le voleur de votre compte, maintenant.',
                    'actions' => [
                        ['title' => 'Changez votre mot de passe',
                         'detail' => 'Si vous pouvez encore vous connecter, faites-le tout de suite. Si l\'ordinateur d\'où vous tentez vous semble bizarre (lent, étrange), faites-le depuis un autre appareil — votre téléphone par exemple. Choisissez un mot de passe que vous n\'utilisez nulle part ailleurs.'],
                        ['title' => 'Ajoutez la validation en deux étapes',
                         'detail' => 'C\'est un second verrou. À la connexion, le service vous envoie un code à six chiffres (sur votre téléphone, par SMS ou via une petite application). Sans ce code, même quelqu\'un qui a votre mot de passe ne peut pas entrer. Cherchez dans les paramètres « sécurité » du service, l\'option s\'appelle souvent « validation en deux étapes » ou « authentification à deux facteurs ».'],
                        ['title' => 'Déconnectez tous les autres appareils',
                         'detail' => 'Dans les paramètres de votre compte, cherchez une option du genre « Se déconnecter de tous les appareils » ou « Mes sessions actives ». Cliquez. Le voleur perd l\'accès, même s\'il a réussi à se connecter avant vous.'],
                        ['title' => 'Vérifiez l\'adresse mail de récupération',
                         'detail' => 'C\'est l\'adresse que le service utilise pour vous renvoyer un mot de passe oublié. Le voleur a peut-être mis la sienne pour pouvoir reprendre le compte plus tard. Allez dans les paramètres, vérifiez que c\'est bien votre adresse à vous, sinon remettez-la.'],
                    ],
                ],
                [
                    'key' => 'day',
                    'label' => 'Dans les 24 heures',
                    'note'  => 'On limite les dégâts autour. Le piratage d\'un compte mène souvent à d\'autres galères dans la foulée.',
                    'actions' => [
                        ['title' => 'Prévenez vos proches',
                         'detail' => 'Un message simple suffit : « Mon compte a été piraté hier soir. Si vous avez reçu un message bizarre de moi, ne cliquez sur rien et ne répondez pas. Tout va bien maintenant. » Ça évite qu\'ils se fassent eux-mêmes piéger par une arnaque qui se fait passer pour vous.'],
                        ['title' => 'Vérifiez vos autres comptes',
                         'detail' => 'Si vous utilisiez le même mot de passe ailleurs (même un seul), changez-le partout. C\'est le piège le plus courant : le voleur teste votre mot de passe sur la banque, sur Amazon, sur votre boîte mail. Et hop, deux comptes pour le prix d\'un.'],
                        ['title' => 'Signalez à la plateforme',
                         'detail' => 'Facebook, Instagram, Gmail, Outlook ont tous une procédure « j\'ai été piraté ». Cherchez « compte piraté » dans leur centre d\'aide. Ça déclenche une vérification de leur côté et ça vous protège juridiquement si jamais le voleur fait quelque chose de grave en votre nom.'],
                        ['title' => 'Si la banque est concernée, opposition tout de suite',
                         'detail' => 'Le numéro est au dos de votre carte, ou dans votre application bancaire (rubrique « urgence » ou « bloquer ma carte »). Vous pouvez aussi appeler votre conseiller. Ne perdez pas une heure : la fraude bancaire se prouve d\'autant mieux qu\'on agit vite.'],
                    ],
                ],
                [
                    'key' => 'after',
                    'label' => 'Pour que ça n\'arrive plus',
                    'note'  => 'Le calme est revenu. C\'est le moment d\'installer deux ou trois choses durables.',
                    'actions' => [
                        ['title' => 'Prenez un coffre à mots de passe',
                         'detail' => 'Une petite application qui retient vos mots de passe à votre place. Vous n\'en mémorisez plus qu\'un seul (le mot de passe maître), elle s\'occupe du reste. Bitwarden est gratuit, sérieux et marche sur tous vos appareils. 1Password coûte 3 € par mois mais est plus joli si ça compte pour vous.'],
                        ['title' => 'Mettez la validation en deux étapes partout',
                         'detail' => 'Sur votre boîte mail principale d\'abord (c\'est la clé qui ouvre tous les autres comptes), puis sur la banque, puis sur les réseaux sociaux. Ça prend cinq minutes par compte, et ça bloque 99 % des piratages.'],
                        ['title' => 'Surveillez les fuites',
                         'detail' => 'Le site haveibeenpwned.com (en anglais, mais les seuls boutons à cliquer sont en haut) vérifie si votre adresse mail apparaît dans une fuite connue. Vous pouvez vous inscrire pour être prévenu si ça arrive plus tard. Gratuit, sérieux, créé par un chercheur de Microsoft.'],
                    ],
                ],
            ],
            'resources' => [
                ['label' => 'Cybermalveillance · Compte piraté', 'url' => 'https://www.cybermalveillance.gouv.fr/tous-nos-contenus/fiches-reflexes/piratage-de-compte-en-ligne', 'kind' => 'official'],
                ['label' => 'Have I Been Pwned · vérifier mon adresse', 'url' => 'https://haveibeenpwned.com', 'kind' => 'tool'],
            ],
            'related' => ['phishing-clic-lien', 'fuite-donnees'],
        ];
    }

    /** @return array<string, mixed> */
    private function scenarioPhishing(): array
    {
        return [
            'slug'    => 'phishing-clic-lien',
            'title'   => 'J\'ai cliqué sur un lien suspect',
            'kicker'  => 'SMS · Mail · Faux site',
            'summary' => 'Un SMS « Ameli » qui met la pression. Un mail « Chronopost » qui demande deux euros. Vous avez cliqué et vous avez peut-être tapé quelque chose. Pas de panique, on regarde ce qu\'il faut faire selon ce que vous avez saisi.',
            'urgency' => 'urgent',
            'diagnosis' => [
                'Vous avez cliqué sur un lien dans un SMS ou un mail qui semblait urgent.',
                'La page d\'arrivée ressemblait à votre banque, à Ameli, à La Poste, à Chronopost…',
                'Vous avez peut-être tapé un mot de passe, un numéro de carte ou autre chose.',
                'Vous avez ce petit doute juste après le clic : « j\'aurais pas dû ».',
            ],
            'steps' => [
                [
                    'key' => 'immediate',
                    'label' => 'Dans les 10 prochaines minutes',
                    'note'  => 'Tout dépend de ce que vous avez tapé sur la fausse page. Lisez la ligne qui vous concerne.',
                    'actions' => [
                        ['title' => 'Vous avez tapé un mot de passe',
                         'detail' => 'Allez tout de suite sur le vrai site (en tapant l\'adresse vous-même dans le navigateur, pas en cliquant sur un lien) et changez-le. Si c\'était le mot de passe que vous utilisez ailleurs aussi, changez-le partout. Et ajoutez la validation en deux étapes au passage : c\'est ce code à six chiffres que le service vous envoie à la connexion, en plus du mot de passe.'],
                        ['title' => 'Vous avez tapé un numéro de carte bancaire',
                         'detail' => 'Faites opposition tout de suite. Le numéro est au dos de la carte, ou dans votre application bancaire (rubrique « urgence »). Ne réfléchissez pas, ne discutez pas le « ce n\'était que deux euros » : faites opposition. La banque refait une carte gratuitement.'],
                        ['title' => 'Vous avez donné un RIB ou un IBAN',
                         'detail' => 'Appelez votre banque. Demandez à mettre une surveillance sur les prélèvements. Si quelqu\'un essaie de débiter, ils bloquent automatiquement. C\'est gratuit, ça prend cinq minutes.'],
                        ['title' => 'Vous avez juste cliqué, sans rien taper',
                         'detail' => 'Le risque est très faible. Vérifiez quand même qu\'aucun fichier ne s\'est téléchargé : ouvrez les téléchargements de votre navigateur (raccourci Ctrl+J ou Cmd+J). Si quelque chose est apparu, ne l\'ouvrez surtout pas, mettez-le à la corbeille.'],
                    ],
                ],
                [
                    'key' => 'day',
                    'label' => 'Dans les 24 heures',
                    'note'  => 'Maintenant on surveille, et on signale pour aider à faire fermer la fausse page.',
                    'actions' => [
                        ['title' => 'Surveillez vos comptes pendant deux jours',
                         'detail' => 'Banque, boîte mail, réseaux sociaux. Si vous voyez un mouvement bizarre, vous le coupez immédiatement (opposition, changement de mot de passe, blocage du compte).'],
                        ['title' => 'Signalez l\'arnaque',
                         'detail' => 'Si c\'était un SMS : transférez-le au 33700, c\'est gratuit, c\'est le service officiel. Si c\'était un mail : passez par signal-spam.fr (un clic, ça enregistre l\'arnaque). Si c\'était un site web : phishing-initiative.fr le signale aux navigateurs, qui mettent une alerte rouge pour la prochaine victime.'],
                        ['title' => 'Si de l\'argent a vraiment été pris',
                         'detail' => 'La banque a 30 jours pour vous rembourser sur réclamation. Portez plainte au commissariat ou en ligne sur pre-plainte-en-ligne.gouv.fr — c\'est rapide et ça vous protège juridiquement.'],
                    ],
                ],
                [
                    'key' => 'after',
                    'label' => 'Pour reconnaître la prochaine',
                    'note'  => 'Ces arnaques marchent parce qu\'elles vous font peur ou vous pressent. La règle qui marche tout le temps : reposez le téléphone, allez voir vous-même.',
                    'actions' => [
                        ['title' => 'Regardez l\'adresse de l\'expéditeur',
                         'detail' => 'Un vrai SMS de la banque ne contient pratiquement jamais de lien cliquable. Un vrai mail Ameli vient de @ameli.fr, pas de @ameli-services.fr ou @ameli.assurance-maladie.com. Le « point fr » officiel est court et sans suffixe.'],
                        ['title' => 'Allez voir vous-même, sans cliquer',
                         'detail' => 'Plutôt que de cliquer sur le lien du message, ouvrez l\'application de votre banque, ou tapez ameli.fr dans votre navigateur. Si l\'info était vraie, elle est aussi là. Si elle n\'y est pas, vous avez votre réponse.'],
                        ['title' => 'Activez les alertes paiement de votre banque',
                         'detail' => 'Toutes les banques proposent une notification gratuite à chaque paiement par carte. Cherchez « notifications » ou « alertes » dans les paramètres de votre application. Une fraude qui prendrait normalement deux semaines à être repérée se voit en cinq minutes.'],
                    ],
                ],
            ],
            'resources' => [
                ['label' => 'Signaler un SMS au 33700',                'url' => 'https://www.33700.fr',                       'kind' => 'official'],
                ['label' => 'Signal Spam · signaler un mail',          'url' => 'https://www.signal-spam.fr',                 'kind' => 'official'],
                ['label' => 'Phishing Initiative · signaler un site',  'url' => 'https://phishing-initiative.fr',             'kind' => 'official'],
                ['label' => 'Pré-plainte en ligne',                    'url' => 'https://www.pre-plainte-en-ligne.gouv.fr',   'kind' => 'official'],
            ],
            'related' => ['compte-pirate', 'fuite-donnees'],
        ];
    }

    /** @return array<string, mixed> */
    private function scenarioVirus(): array
    {
        return [
            'slug'    => 'virus-rancongiciel',
            'title'   => 'Mon ordinateur est bloqué',
            'kicker'  => 'Logiciel malveillant · Demande de rançon · Pop-up',
            'summary' => 'Un message demande une rançon pour « débloquer » l\'ordinateur. Vos fichiers ne s\'ouvrent plus. Des pop-ups n\'arrêtent pas. C\'est ce qu\'on appelle un rançongiciel : un logiciel qui prend vos données en otage. On va agir méthodiquement et ne surtout rien payer.',
            'urgency' => 'critical',
            'diagnosis' => [
                'Un message couvre tout l\'écran et demande de payer (souvent en cryptomonnaie) pour « libérer » votre ordinateur.',
                'Vos fichiers ont changé d\'extension (.locked, .crypt, etc.) et refusent de s\'ouvrir.',
                'L\'ordinateur est très lent, le ventilateur tourne en permanence sans raison.',
                'Des fenêtres de pub s\'ouvrent en cascade et votre antivirus s\'est éteint tout seul.',
                'Le navigateur affiche une page « Support Microsoft » avec un numéro à appeler — c\'est faux.',
            ],
            'steps' => [
                [
                    'key' => 'immediate',
                    'label' => 'Dans les 10 prochaines minutes',
                    'note'  => 'On isole l\'ordinateur. On ne paie pas. On ne touche à rien d\'autre.',
                    'actions' => [
                        ['title' => 'Coupez Internet',
                         'detail' => 'Désactivez le Wi-Fi (en haut à droite de l\'écran sur Mac, en bas à droite sur Windows), et débranchez le câble réseau s\'il y en a un. Ça empêche le logiciel malveillant de se propager à d\'autres appareils de la maison et de communiquer avec ses créateurs.'],
                        ['title' => 'Ne payez pas la rançon. Jamais.',
                         'detail' => 'Plusieurs raisons. D\'abord, ça ne garantit rien : beaucoup de victimes paient et ne récupèrent jamais leurs fichiers. Ensuite, ça finance et encourage les attaquants à recommencer chez quelqu\'un d\'autre. Enfin, vous vous identifiez comme « cible solvable » et les demandes peuvent recommencer plus tard.'],
                        ['title' => 'Ne redémarrez pas l\'ordinateur',
                         'detail' => 'Certains logiciels malveillants finissent leur travail au redémarrage, ou effacent des traces qui aident les enquêteurs à identifier qui est derrière. On laisse l\'ordinateur allumé, juste sans réseau.'],
                        ['title' => 'Prenez en photo le message de rançon',
                         'detail' => 'Avec votre téléphone (vous ne pouvez plus utiliser l\'ordinateur). C\'est utile pour la plainte, et surtout ça permet d\'identifier quel logiciel malveillant est en cause — certains ont des outils gratuits pour les déverrouiller.'],
                        ['title' => 'Débranchez vos clés USB et disques externes',
                         'detail' => 'Si quelque chose est encore branché à l\'ordinateur, ça pourrait être contaminé aussi. Débranchez tout, y compris un disque dur de sauvegarde s\'il était connecté. Ne le rebranchez pas tant qu\'on n\'a pas réglé le problème.'],
                    ],
                ],
                [
                    'key' => 'day',
                    'label' => 'Dans les 24 heures',
                    'note'  => 'On dépose plainte, on cherche un déverrouillage gratuit, on évalue ce qu\'on peut récupérer.',
                    'actions' => [
                        ['title' => 'Déposez plainte',
                         'detail' => 'Allez sur pre-plainte-en-ligne.gouv.fr pour préparer le dossier, puis prenez rendez-vous au commissariat le plus proche. Apportez la photo du message et toutes les infos que vous avez (date, comment ça a commencé, fichiers touchés). Les enquêteurs savent traiter ces affaires.'],
                        ['title' => 'Vérifiez nomoreransom.org',
                         'detail' => 'C\'est un site officiel européen (Europol + plusieurs polices nationales) qui propose des outils gratuits pour déverrouiller les fichiers, selon le type de logiciel qui vous a frappé. Vous y déposez un fichier verrouillé, le site identifie le coupable et vous donne l\'outil s\'il existe. Ça vaut toujours la peine d\'essayer avant tout autre chose.'],
                        ['title' => 'Contactez Cybermalveillance.gouv.fr',
                         'detail' => 'C\'est le service officiel français d\'aide aux victimes. Ils vous orientent vers des prestataires sérieux dans votre région si vous avez besoin d\'un dépannage. Demandez deux ou trois devis avant de confier votre ordinateur à n\'importe qui.'],
                        ['title' => 'Faites le point sur ce qui est perdu',
                         'detail' => 'Quels fichiers sont vraiment importants ? Avez-vous une sauvegarde quelque part ? Cloud (iCloud, Google Drive, OneDrive), disque dur externe non branché au moment de l\'attaque, vieille sauvegarde sur clé USB ? Si oui, l\'enjeu retombe énormément.'],
                    ],
                ],
                [
                    'key' => 'after',
                    'label' => 'Pour que ça n\'arrive plus',
                    'note'  => 'La sauvegarde est le seul truc qui transforme un drame en désagrément. C\'est la chose à mettre en place absolument.',
                    'actions' => [
                        ['title' => 'Faites une vraie sauvegarde régulière',
                         'detail' => 'Le bon plan : une sauvegarde dans le cloud (iCloud, OneDrive ou Google Drive selon votre matériel) qui se fait toute seule, et une seconde sur un disque dur externe que vous branchez une fois par mois et que vous rangez ensuite dans un tiroir. Le logiciel malveillant ne peut pas atteindre un disque qui n\'est pas branché.'],
                        ['title' => 'Mettez à jour votre système et vos applis',
                         'detail' => 'La grande majorité des infections passent par une faille déjà corrigée par le fabricant — sauf que la victime n\'avait pas fait la mise à jour. Activez les mises à jour automatiques de Windows, macOS, ou de votre antivirus. Quand votre ordinateur dit « il faut redémarrer pour finir l\'installation », faites-le le soir.'],
                        ['title' => 'Méfiez-vous des pièces jointes étranges',
                         'detail' => 'Surtout les fichiers avec des extensions inhabituelles : .zip, .iso, .docm, .xlsm. En cas de doute, ne cliquez pas, et demandez confirmation à l\'expéditeur par un autre moyen (un coup de fil, par exemple). Les vrais collègues ou la vraie banque ne vous en voudront pas de demander.'],
                    ],
                ],
            ],
            'resources' => [
                ['label' => 'No More Ransom · déverrouillage gratuit',           'url' => 'https://www.nomoreransom.org/fr/',                                                                                            'kind' => 'tool'],
                ['label' => 'Cybermalveillance · Rançongiciel',                  'url' => 'https://www.cybermalveillance.gouv.fr/tous-nos-contenus/fiches-reflexes/rancongiciels-ransomwares',                          'kind' => 'official'],
                ['label' => 'Pré-plainte en ligne',                              'url' => 'https://www.pre-plainte-en-ligne.gouv.fr',                                                                                    'kind' => 'official'],
            ],
            'related' => ['phishing-clic-lien', 'fuite-donnees'],
        ];
    }

    /** @return array<string, mixed> */
    private function scenarioFuite(): array
    {
        return [
            'slug'    => 'fuite-donnees',
            'title'   => 'Mes données sont dans une fuite',
            'kicker'  => 'Fuite de données · Vol d\'informations',
            'summary' => 'Une plateforme s\'est fait pirater et vos infos personnelles (mail, mot de passe, parfois plus) circulent quelque part. C\'est plus fréquent qu\'on ne croit. Voici ce qui change pour vous, et les gestes simples qui réduisent le risque.',
            'urgency' => 'serious',
            'diagnosis' => [
                'Vous recevez un mail (en anglais souvent) qui dit que votre adresse est apparue dans une fuite.',
                'Une plateforme vous écrit pour vous prévenir « vos données ont été compromises ».',
                'Votre numéro de téléphone reçoit beaucoup d\'appels et SMS d\'arnaque qui vous appellent par votre prénom.',
                'Un proche vous dit avoir trouvé votre ancien mot de passe sur un forum.',
            ],
            'steps' => [
                [
                    'key' => 'immediate',
                    'label' => 'Dans les 10 prochaines minutes',
                    'note'  => 'On rend votre mot de passe fuité inutile, c\'est le plus important.',
                    'actions' => [
                        ['title' => 'Changez le mot de passe du service en cause',
                         'detail' => 'Allez sur le site (en tapant l\'adresse vous-même, pas via un lien dans un mail), connectez-vous, et changez votre mot de passe. Activez aussi la validation en deux étapes pendant que vous y êtes : c\'est un code à six chiffres en plus du mot de passe, qui bloque l\'usage de mots de passe volés.'],
                        ['title' => 'Changez-le partout où vous l\'utilisiez',
                         'detail' => 'C\'est le piège classique : on a tendance à utiliser le même mot de passe sur plusieurs sites. Dès qu\'un voleur l\'a obtenu, il l\'essaie partout. Listez les sites où vous saviez l\'utiliser et changez-le. Si vous n\'arrivez plus à vous souvenir où, faites-le sur les comptes les plus importants : boîte mail, banque, réseaux sociaux.'],
                        ['title' => 'Regardez ce qui a fuité exactement',
                         'detail' => 'Le site haveibeenpwned.com (créé par un chercheur de Microsoft, gratuit, sérieux) vous dit précisément quelles infos sont sorties : juste l\'email, ou aussi le nom, l\'adresse, le téléphone, le numéro de carte bancaire. Selon ce qui a fuité, les actions à prendre changent.'],
                    ],
                ],
                [
                    'key' => 'day',
                    'label' => 'Dans les 24 heures',
                    'note'  => 'On prévient les bons interlocuteurs selon ce qui a fuité.',
                    'actions' => [
                        ['title' => 'Si un IBAN ou un numéro de carte ont fuité',
                         'detail' => 'Appelez votre banque. Demandez-leur de mettre une surveillance sur les prélèvements (ils refusent automatiquement les paiements suspects), et selon le cas, refaire la carte. C\'est gratuit, ça prend dix minutes.'],
                        ['title' => 'Méfiez-vous pendant un mois',
                         'detail' => 'Avec votre nom, votre adresse et votre dernier achat, un escroc peut écrire un mail qui semble très crédible. Pendant quatre à six semaines après une fuite, soyez plus suspicieux que d\'habitude : un mail urgent, un SMS bizarre, un appel d\'un « conseiller » — vous ralentissez et vous vérifiez.'],
                        ['title' => 'Si la fuite vient d\'un gros opérateur',
                         'detail' => 'Free, Bouygues, votre banque, votre opérateur de santé… Vous pouvez les saisir directement ET passer par la CNIL si la réponse vous paraît insuffisante. La CNIL est le service de l\'État qui protège vos données : ils ont vraiment des dents et ils peuvent infliger des amendes.'],
                    ],
                ],
                [
                    'key' => 'after',
                    'label' => 'Pour limiter les fuites futures',
                    'note'  => 'Vos données fuiteront encore, c\'est inévitable. Le but maintenant est qu\'une fuite ne mette plus aucun autre compte en danger.',
                    'actions' => [
                        ['title' => 'Un mot de passe différent par site',
                         'detail' => 'Avec un coffre à mots de passe (Bitwarden, gratuit) : vous tapez votre mot de passe maître une fois, l\'application remplit le reste. Une fuite reste alors limitée au seul service concerné, sans toucher tous vos autres comptes.'],
                        ['title' => 'Utilisez des adresses « jetables »',
                         'detail' => 'Pour ne pas donner votre vraie adresse mail à tous les sites du monde, vous pouvez créer des alias : une adresse différente par service. Si l\'une se met à recevoir trop de spam ou apparaît dans une fuite, vous la coupez et c\'est fini. SimpleLogin (gratuit, français) fait ça très bien. Si vous êtes sur iPhone, Apple propose la même chose en natif (« Masquer mon adresse mail »).'],
                        ['title' => 'Activez les alertes de fuite',
                         'detail' => 'Sur haveibeenpwned.com il y a une page « Notify me » qui vous prévient à la prochaine fuite vous concernant. Gratuit, c\'est juste votre adresse mail. Vous saurez avant les voleurs.'],
                    ],
                ],
            ],
            'resources' => [
                ['label' => 'Have I Been Pwned · vérifier votre adresse',  'url' => 'https://haveibeenpwned.com',                       'kind' => 'tool'],
                ['label' => 'CNIL · déposer une plainte',                  'url' => 'https://www.cnil.fr/fr/plaintes',                  'kind' => 'official'],
                ['label' => 'Cybermalveillance · Fuite de données',        'url' => 'https://www.cybermalveillance.gouv.fr/tous-nos-contenus/actualites/violation-donnees-personnelles', 'kind' => 'official'],
            ],
            'related' => ['compte-pirate', 'phishing-clic-lien'],
        ];
    }

    /** @return array<string, mixed> */
    private function scenarioSextorsion(): array
    {
        return [
            'slug'    => 'sextorsion',
            'title'   => 'On me menace de chantage',
            'kicker'  => 'Chantage · Menace de diffusion · Webcam',
            'summary' => 'Quelqu\'un menace de diffuser des photos, des vidéos, ou une conversation. Vous n\'êtes pas seul·e, ça arrive à beaucoup de monde et il y a une marche à suivre claire. La première règle, immuable : on ne paie pas.',
            'urgency' => 'critical',
            'diagnosis' => [
                'Un message vous accuse d\'avoir filmé votre webcam sans le savoir, et exige un paiement (souvent en bitcoin).',
                'Quelqu\'un menace de diffuser des photos ou vidéos intimes à vos proches ou à votre patron.',
                'Le message cite un de vos anciens mots de passe (ça vient quasi toujours d\'une fuite, pas d\'une vraie intrusion).',
                'Une personne rencontrée récemment en ligne fait du chantage après un échange intime.',
            ],
            'steps' => [
                [
                    'key' => 'immediate',
                    'label' => 'Dans les 10 prochaines minutes',
                    'note'  => 'Trois règles, dans cet ordre. Pas de discussion, pas d\'écart.',
                    'actions' => [
                        ['title' => 'Ne payez pas. Sous aucun prétexte.',
                         'detail' => 'Le paiement n\'arrête rien. Au contraire, il prouve à la personne en face que vous êtes une cible qui paie, et les demandes recommencent quelques semaines plus tard avec des montants plus gros. Dans la grande majorité des cas, l\'attaquant n\'a en fait rien d\'incriminant et bluffe.'],
                        ['title' => 'Ne répondez pas',
                         'detail' => 'Aucun message, même pas pour dire « non ». Toute réponse relance la pression et donne à la personne en face de quoi vous relancer. Le silence est la meilleure défense, c\'est rare mais c\'est vrai.'],
                        ['title' => 'Conservez les preuves',
                         'detail' => 'Faites des captures d\'écran complètes du message (avec la date, le nom du compte, l\'adresse mail si visible). Notez les liens vers les profils, les numéros de téléphone, les adresses bitcoin si données. Ne supprimez rien : tout cela servira pour la plainte. Mettez les captures dans un dossier que vous ne perdrez pas.'],
                    ],
                ],
                [
                    'key' => 'day',
                    'label' => 'Dans les 24 heures',
                    'note'  => 'Vous portez plainte. Vous parlez à quelqu\'un. Vous bloquez l\'expéditeur.',
                    'actions' => [
                        ['title' => 'Portez plainte',
                         'detail' => 'Au commissariat, ou en ligne sur pre-plainte-en-ligne.gouv.fr. C\'est un délit puni par la loi (chantage : 5 ans de prison, 75 000 € d\'amende). Les enquêteurs traitent ces affaires régulièrement et savent que la victime n\'a rien à se reprocher. Vous serez écouté·e sans jugement.'],
                        ['title' => 'Signalez sur Pharos',
                         'detail' => 'C\'est la plateforme officielle française pour signaler les contenus illicites en ligne : internet-signalement.gouv.fr. Cinq minutes, anonyme si vous voulez. Ça déclenche un suivi par la police nationale.'],
                        ['title' => 'Bloquez la personne sur tous les canaux',
                         'detail' => 'Sur le réseau social, sur la messagerie, sur le mail. Plus aucune notification, plus aucun contact. Si la menace passe par un réseau social, signalez le compte à la plateforme — Instagram, Facebook, TikTok ont tous une procédure pour ça, et ils suppriment souvent dans la journée.'],
                        ['title' => 'Parlez-en à quelqu\'un de confiance',
                         'detail' => 'Un proche, un médecin, une association. Ne restez pas seul·e avec ça, c\'est éprouvant. Si vous êtes mineur·e, ou un·e jeune dans votre entourage : le 3018 (Net Écoute) est gratuit, anonyme, ouvert 7j/7, et ce sont des professionnels qui savent gérer.'],
                    ],
                ],
                [
                    'key' => 'after',
                    'label' => 'Reprendre la main durablement',
                    'note'  => 'On verrouille la vie privée et on n\'oublie pas une chose : vous, vous n\'avez rien fait de mal.',
                    'actions' => [
                        ['title' => 'Verrouillez vos profils',
                         'detail' => 'Mettez vos comptes Instagram, Facebook, TikTok en privé. Cachez votre liste d\'amis. Repassez vos vieilles photos en privé. Ça réduit énormément ce qu\'un attaquant peut récupérer s\'il essaie de rassembler des infos sur vous.'],
                        ['title' => 'Si du contenu a été diffusé, demandez le retrait',
                         'detail' => 'Toutes les grandes plateformes (Instagram, TikTok, Facebook, X) ont une procédure « contenu intime non consenti » qui retire vite (en quelques heures souvent). Pharos et Cybermalveillance peuvent aussi vous accompagner pour faire retirer du contenu d\'autres sites.'],
                        ['title' => 'Prenez soin de vous',
                         'detail' => 'C\'est éprouvant émotionnellement. France Victimes (numéro gratuit 116 006) propose un accompagnement psychologique et juridique. Votre médecin traitant peut aussi vous orienter. Aucune honte à prendre soin de soi après ça — au contraire, c\'est ce qui marche.'],
                    ],
                ],
            ],
            'resources' => [
                ['label' => 'Pharos · signaler un contenu',                    'url' => 'https://www.internet-signalement.gouv.fr',                                                                                            'kind' => 'official'],
                ['label' => 'Net Écoute · 3018 (jeunes et parents)',           'url' => 'https://www.e-enfance.org/numero-3018',                                                                                                'kind' => 'official'],
                ['label' => 'France Victimes · 116 006',                       'url' => 'https://www.france-victimes.fr',                                                                                                       'kind' => 'official'],
                ['label' => 'Cybermalveillance · Chantage à la webcam',        'url' => 'https://www.cybermalveillance.gouv.fr/tous-nos-contenus/fiches-reflexes/chantage-a-la-webcam-pretendue',                              'kind' => 'official'],
                ['label' => 'Pré-plainte en ligne',                            'url' => 'https://www.pre-plainte-en-ligne.gouv.fr',                                                                                            'kind' => 'official'],
            ],
            'related' => ['compte-pirate'],
        ];
    }
}
