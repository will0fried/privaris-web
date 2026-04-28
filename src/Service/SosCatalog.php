<?php

namespace App\Service;

/**
 * Catalogue des scénarios SOS — un guide d'action pour les personnes en panique.
 *
 * Le contenu est volontairement en code (et non en base) pour 3 raisons :
 *   1. Stabilité : ces 5 scénarios évoluent rarement, ils sont curatés.
 *   2. Qualité : le ton et la structure doivent être très précis. Pas de risque
 *      qu'un brouillon admin se retrouve en prod sur une page d'urgence.
 *   3. Performance : pas de hit DB, mise en cache trivial.
 *
 * Structure d'un scénario :
 *   slug         : identifiant URL
 *   title        : titre court (page et card)
 *   kicker       : sur-titre éditorial (SEV ou typologie)
 *   summary      : phrase d'accroche (carte de la liste + meta description)
 *   urgency      : 'critical' | 'urgent' | 'serious' — pilote la couleur du badge
 *   diagnosis    : liste de "Vous êtes concerné si…"
 *   steps        : 3 buckets séquentiels (immediate, day, after) avec un titre
 *                  et une liste d'actions. Chaque action peut avoir un détail.
 *   resources    : liens officiels (cybermalveillance, signal-arnaques, etc.)
 *   related      : slugs des scénarios voisins (cross-link bas de page)
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
            'kicker'  => 'Email · Réseau social · Banque',
            'summary' => 'Quelqu\'un est entré dans votre compte. Mots de passe modifiés, messages bizarres envoyés, transactions inconnues. Voici comment reprendre la main.',
            'urgency' => 'urgent',
            'diagnosis' => [
                'Votre mot de passe ne fonctionne plus alors que vous ne l\'avez pas changé.',
                'Vos contacts reçoivent des messages que vous n\'avez pas envoyés.',
                'Votre boîte mail contient des emails envoyés que vous ne reconnaissez pas.',
                'Vous recevez une notification de connexion depuis un appareil inconnu.',
                'Une transaction bancaire ou un achat apparaît sans vous.',
            ],
            'steps' => [
                [
                    'key' => 'immediate',
                    'label' => 'Dans les 10 prochaines minutes',
                    'note'  => 'On stoppe l\'hémorragie. Pas de réflexion, on agit.',
                    'actions' => [
                        ['title' => 'Changez le mot de passe',         'detail' => 'Depuis un autre appareil si possible (téléphone si c\'est l\'ordi qui est compromis, et inversement). Choisissez un mot de passe long que vous n\'utilisez nulle part ailleurs.'],
                        ['title' => 'Activez la double authentification (2FA)', 'detail' => 'Dans les réglages de sécurité du service. Préférez une application (Google Authenticator, Authy) plutôt que les SMS.'],
                        ['title' => 'Déconnectez toutes les autres sessions', 'detail' => 'La plupart des services proposent « Se déconnecter de tous les autres appareils ». Faites-le. L\'attaquant perd l\'accès.'],
                        ['title' => 'Vérifiez l\'email de récupération', 'detail' => 'L\'attaquant l\'a peut-être changé pour garder un point d\'entrée. Remettez le vôtre.'],
                    ],
                ],
                [
                    'key' => 'day',
                    'label' => 'Dans les 24 heures',
                    'note'  => 'On contient les dégâts collatéraux et on prévient.',
                    'actions' => [
                        ['title' => 'Prévenez vos contacts',           'detail' => 'Un message simple : « Mon compte a été piraté, n\'ouvrez pas les messages bizarres de ma part hier. » Ça leur évite de tomber dans une arnaque qui se présente comme vous.'],
                        ['title' => 'Vérifiez tous vos autres comptes', 'detail' => 'Si vous utilisiez le même mot de passe ailleurs (mail, banque, réseaux sociaux), changez-le partout. C\'est le vecteur n°1 de propagation.'],
                        ['title' => 'Signalez à la plateforme',        'detail' => 'Facebook, Instagram, Gmail, Outlook ont tous une procédure « compte piraté ». Lancez-la, ça déclenche une enquête et vous protège juridiquement.'],
                        ['title' => 'Si banque concernée → opposition immédiate', 'detail' => 'Appelez votre banque. Numéro d\'opposition au dos de la carte ou via l\'appli. Ne perdez pas une heure.'],
                    ],
                ],
                [
                    'key' => 'after',
                    'label' => 'Pour que ça n\'arrive plus',
                    'note'  => 'Une fois le calme revenu, on installe des verrous durables.',
                    'actions' => [
                        ['title' => 'Adoptez un gestionnaire de mots de passe', 'detail' => 'Bitwarden, 1Password, Proton Pass. Un mot de passe différent par site, généré aléatoirement. Vous ne retiendrez plus que le mot de passe maître.'],
                        ['title' => 'Activez la 2FA partout',           'detail' => 'Mail principal, banque, réseaux sociaux, achat en ligne. Un attaquant qui a votre mot de passe n\'entre quand même pas.'],
                        ['title' => 'Surveillez les fuites',            'detail' => 'Inscrivez votre email sur haveibeenpwned.com — vous serez prévenu si vos identifiants apparaissent dans une nouvelle fuite.'],
                    ],
                ],
            ],
            'resources' => [
                ['label' => 'Cybermalveillance.gouv.fr · Compte piraté', 'url' => 'https://www.cybermalveillance.gouv.fr/tous-nos-contenus/fiches-reflexes/piratage-de-compte-en-ligne', 'kind' => 'official'],
                ['label' => 'Have I Been Pwned',                          'url' => 'https://haveibeenpwned.com',                                                                                  'kind' => 'tool'],
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
            'summary' => 'Un SMS Ameli, un mail Chronopost, un message de la banque. Vous avez cliqué, peut-être saisi quelque chose. On limite la casse maintenant.',
            'urgency' => 'urgent',
            'diagnosis' => [
                'Vous avez cliqué sur un lien dans un SMS ou un mail qui paraissait urgent.',
                'La page ressemblait à votre banque, à Ameli, à La Poste, à Chronopost.',
                'Vous avez (peut-être) saisi votre identifiant, mot de passe, ou numéro de carte.',
                'Vous avez ce doute juste après : « j\'aurais pas dû ».',
            ],
            'steps' => [
                [
                    'key' => 'immediate',
                    'label' => 'Dans les 10 prochaines minutes',
                    'note'  => 'Le critère, c\'est : qu\'avez-vous saisi sur la fausse page ?',
                    'actions' => [
                        ['title' => 'Vous avez saisi un mot de passe',          'detail' => 'Changez-le immédiatement sur le vrai site. Si vous l\'utilisiez ailleurs, changez-le partout aussi. Activez la 2FA.'],
                        ['title' => 'Vous avez saisi un numéro de carte bancaire', 'detail' => 'Faites opposition tout de suite. Numéro au dos de votre carte, ou dans votre appli bancaire. Ne discutez pas le « petit montant », faites opposition.'],
                        ['title' => 'Vous avez saisi des coordonnées bancaires (RIB, IBAN)', 'detail' => 'Appelez votre banque. Demandez à mettre un blocage sur les prélèvements non reconnus.'],
                        ['title' => 'Vous n\'avez rien saisi mais vous avez cliqué', 'detail' => 'Le risque est très limité (sauf téléchargement). Vérifiez quand même que rien ne s\'est téléchargé : ouvrez les téléchargements de votre navigateur.'],
                    ],
                ],
                [
                    'key' => 'day',
                    'label' => 'Dans les 24 heures',
                    'note'  => 'On surveille les conséquences, on signale.',
                    'actions' => [
                        ['title' => 'Surveillez vos comptes',          'detail' => 'Comptes bancaires, mail, réseaux sociaux. Tout mouvement bizarre = action immédiate.'],
                        ['title' => 'Signalez l\'arnaque',             'detail' => 'SMS frauduleux : transférez au 33700 (gratuit). Mail frauduleux : signal-spam.fr. Site web frauduleux : phishing-initiative.fr.'],
                        ['title' => 'Si fraude bancaire avérée',       'detail' => 'Demandez le remboursement à la banque (ils ont 30 jours). Portez plainte au commissariat ou en ligne sur pre-plainte-en-ligne.gouv.fr.'],
                    ],
                ],
                [
                    'key' => 'after',
                    'label' => 'Pour reconnaître la prochaine',
                    'note'  => 'Ce qui fait tomber, c\'est l\'urgence. La règle : reposez le téléphone, allez voir vous-même.',
                    'actions' => [
                        ['title' => 'Vérifiez l\'expéditeur', 'detail' => 'Un vrai SMS de la banque ne contient jamais de lien cliquable. Un vrai mail Ameli vient de @ameli.fr, pas de @ameli-services.fr.'],
                        ['title' => 'Allez voir vous-même', 'detail' => 'Plutôt que cliquer sur le lien, ouvrez votre appli ou tapez le site dans votre navigateur. Si c\'est vrai, vous y verrez la même information.'],
                        ['title' => 'Activez les alertes bancaires', 'detail' => 'Notification au moindre paiement. Une fraude est repérée en 5 minutes au lieu de 5 jours.'],
                    ],
                ],
            ],
            'resources' => [
                ['label' => 'Signaler un SMS au 33700',                'url' => 'https://www.33700.fr',                       'kind' => 'official'],
                ['label' => 'Signal Spam · Mail frauduleux',           'url' => 'https://www.signal-spam.fr',                 'kind' => 'official'],
                ['label' => 'Phishing Initiative · Site frauduleux',   'url' => 'https://phishing-initiative.fr',             'kind' => 'official'],
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
            'title'   => 'Mon ordinateur est bloqué ou rançonné',
            'kicker'  => 'Rançongiciel · Virus · Pop-up',
            'summary' => 'Un message qui prend tout l\'écran, des fichiers devenus illisibles, un ordinateur qui rame depuis ce matin. On agit méthodiquement.',
            'urgency' => 'critical',
            'diagnosis' => [
                'Un message plein écran demande une rançon (souvent en cryptomonnaie) pour « débloquer » l\'ordinateur.',
                'Vos fichiers ont une extension nouvelle (.locked, .crypt, etc.) et ne s\'ouvrent plus.',
                'L\'ordinateur est extrêmement lent, le ventilateur tourne sans raison.',
                'Des pop-ups s\'ouvrent en cascade, votre antivirus est désactivé.',
                'Le navigateur affiche des pubs étranges même hors-ligne, ou une page « support Microsoft » avec un numéro à appeler.',
            ],
            'steps' => [
                [
                    'key' => 'immediate',
                    'label' => 'Dans les 10 prochaines minutes',
                    'note'  => 'On isole. On ne paie pas. On ne touche à rien d\'autre.',
                    'actions' => [
                        ['title' => 'Coupez Internet',           'detail' => 'Désactivez le Wi-Fi, débranchez le câble Ethernet. Empêche la propagation et la communication avec l\'attaquant.'],
                        ['title' => 'NE PAYEZ PAS LA RANÇON',    'detail' => 'Rien ne garantit le déchiffrement, ça encourage les attaquants, et beaucoup de victimes ne récupèrent jamais leurs fichiers même après paiement.'],
                        ['title' => 'NE REDÉMARREZ PAS',         'detail' => 'Certains rançongiciels finissent leur travail au reboot, ou effacent des traces utiles aux enquêteurs.'],
                        ['title' => 'Photographiez le message de rançon', 'detail' => 'Avec votre téléphone. Indispensable pour la plainte et pour identifier la famille de rançongiciel (ce qui peut permettre un déchiffrement gratuit via No More Ransom).'],
                        ['title' => 'Débranchez les disques externes',     'detail' => 'Clés USB, disques durs externes, NAS. Si l\'attaque progresse encore, ça les protège.'],
                    ],
                ],
                [
                    'key' => 'day',
                    'label' => 'Dans les 24 heures',
                    'note'  => 'On déclare, on vérifie s\'il existe un déchiffreur, on évalue.',
                    'actions' => [
                        ['title' => 'Déposez plainte',           'detail' => 'Pré-plainte en ligne sur pre-plainte-en-ligne.gouv.fr puis rendez-vous au commissariat. Apportez la photo du message et tout ce que vous savez.'],
                        ['title' => 'Vérifiez No More Ransom',   'detail' => 'nomoreransom.org propose des outils de déchiffrement gratuits selon la famille de rançongiciel. Ça vaut toujours un essai avant tout autre solution.'],
                        ['title' => 'Contactez Cybermalveillance', 'detail' => 'cybermalveillance.gouv.fr met en relation avec des prestataires labellisés. Demandez plusieurs devis avant de confier votre machine à n\'importe qui.'],
                        ['title' => 'Évaluez les pertes',        'detail' => 'Quels fichiers ? Avez-vous une sauvegarde récente (cloud, disque externe non branché au moment de l\'attaque) ? Si oui, l\'enjeu retombe énormément.'],
                    ],
                ],
                [
                    'key' => 'after',
                    'label' => 'Pour que ça n\'arrive plus',
                    'note'  => 'La sauvegarde est ce qui transforme un drame en désagrément.',
                    'actions' => [
                        ['title' => 'Sauvegardes 3-2-1',         'detail' => '3 copies, 2 supports différents, 1 hors-site (cloud chiffré ou disque déconnecté). Le rançongiciel ne peut pas atteindre ce qui n\'est pas branché.'],
                        ['title' => 'Système et logiciels à jour', 'detail' => 'La majorité des infections passent par une faille déjà corrigée. Les mises à jour automatiques sont votre meilleur antivirus.'],
                        ['title' => 'Méfiance sur les pièces jointes', 'detail' => 'Surtout les .zip, .iso, .docm, .xlsm. En cas de doute, ne pas ouvrir, demander confirmation à l\'expéditeur par un autre canal.'],
                    ],
                ],
            ],
            'resources' => [
                ['label' => 'No More Ransom · Déchiffreurs gratuits',           'url' => 'https://www.nomoreransom.org/fr/',                                                              'kind' => 'tool'],
                ['label' => 'Cybermalveillance.gouv.fr · Rançongiciel',         'url' => 'https://www.cybermalveillance.gouv.fr/tous-nos-contenus/fiches-reflexes/rancongiciels-ransomwares', 'kind' => 'official'],
                ['label' => 'Pré-plainte en ligne',                              'url' => 'https://www.pre-plainte-en-ligne.gouv.fr',                                                      'kind' => 'official'],
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
            'kicker'  => 'Fuite · Data breach · HIBP',
            'summary' => 'Votre email apparaît dans une fuite, votre mot de passe a été retrouvé en clair, votre numéro circule. Voici ce qui change pour vous, et ce qu\'il faut faire.',
            'urgency' => 'serious',
            'diagnosis' => [
                'Have I Been Pwned vous notifie que votre email apparaît dans une nouvelle fuite.',
                'Vous recevez un mail d\'une plateforme : « vos données ont été compromises ».',
                'Votre numéro de téléphone reçoit soudainement des appels et SMS d\'arnaque ciblés.',
                'Vous découvrez votre mot de passe en clair sur un site comme dehashed.com.',
            ],
            'steps' => [
                [
                    'key' => 'immediate',
                    'label' => 'Dans les 10 prochaines minutes',
                    'note'  => 'On rend les données fuitées inutiles à l\'attaquant.',
                    'actions' => [
                        ['title' => 'Changez le mot de passe du service concerné', 'detail' => 'Et activez la 2FA dans la foulée. Le mot de passe fuité ne vaudra plus rien.'],
                        ['title' => 'Changez le partout où vous l\'utilisiez',     'detail' => 'C\'est le piège classique : on réutilise le même mot de passe sur 10 sites, l\'attaquant teste partout dès qu\'il l\'obtient. Listez ces sites et changez tout.'],
                        ['title' => 'Vérifiez ce qui a fuité exactement',          'detail' => 'Have I Been Pwned détaille (email, mdp, nom, adresse, téléphone, dernière IP, IBAN…). Le plan d\'action n\'est pas le même selon les champs.'],
                    ],
                ],
                [
                    'key' => 'day',
                    'label' => 'Dans les 24 heures',
                    'note'  => 'Selon ce qui a fuité, on prévient les bons interlocuteurs.',
                    'actions' => [
                        ['title' => 'Si IBAN ou CB → banque',                'detail' => 'Demandez la mise en surveillance, voire le rejet automatique des prélèvements étrangers. Selon les cas, refaire la carte.'],
                        ['title' => 'Méfiez-vous des arnaques sur-mesure',   'detail' => 'Avec votre nom + email + adresse + dernier achat, un attaquant fait un mail très crédible. Pendant 1-2 mois, soyez plus suspicieux que d\'habitude.'],
                        ['title' => 'Si fuite massive (Free, opérateur, banque) → CNIL', 'detail' => 'Vous pouvez demander des comptes au responsable de traitement. La CNIL aide en cas de réponse insuffisante.'],
                    ],
                ],
                [
                    'key' => 'after',
                    'label' => 'Pour limiter la prochaine fuite',
                    'note'  => 'Vos données fuiteront encore. Le but est qu\'une fuite ne menace plus aucun autre compte.',
                    'actions' => [
                        ['title' => 'Un mot de passe unique par site',     'detail' => 'Avec un gestionnaire de mots de passe. Quand fuite il y a, elle reste contenue à un seul service.'],
                        ['title' => 'Utilisez des alias email',           'detail' => 'SimpleLogin, AnonAddy, ou les alias iCloud/Proton. Un alias par service. Un alias compromis ? Vous le coupez, le service ne peut plus vous joindre, l\'arnaqueur non plus.'],
                        ['title' => 'Activez les alertes HIBP',           'detail' => 'haveibeenpwned.com/NotifyMe — gratuit, vous prévient à la prochaine fuite. Aucune raison de s\'en priver.'],
                    ],
                ],
            ],
            'resources' => [
                ['label' => 'Have I Been Pwned',                  'url' => 'https://haveibeenpwned.com',                       'kind' => 'tool'],
                ['label' => 'CNIL · Plaintes',                    'url' => 'https://www.cnil.fr/fr/plaintes',                   'kind' => 'official'],
                ['label' => 'Cybermalveillance · Fuite de données','url' => 'https://www.cybermalveillance.gouv.fr/tous-nos-contenus/actualites/violation-donnees-personnelles', 'kind' => 'official'],
            ],
            'related' => ['compte-pirate', 'phishing-clic-lien'],
        ];
    }

    /** @return array<string, mixed> */
    private function scenarioSextorsion(): array
    {
        return [
            'slug'    => 'sextorsion',
            'title'   => 'Je suis menacé(e) de chantage',
            'kicker'  => 'Sextorsion · Chantage · Menace de diffusion',
            'summary' => 'Quelqu\'un menace de diffuser une vidéo, des photos, une conversation. Vous n\'êtes pas seul·e. Voici la marche à suivre — et la première règle : ne pas payer.',
            'urgency' => 'critical',
            'diagnosis' => [
                'Un message vous accuse d\'avoir filmé/piraté votre webcam et exige un paiement (souvent en bitcoin).',
                'Quelqu\'un menace de diffuser des photos ou vidéos intimes à vos contacts.',
                'Le message cite un de vos anciens mots de passe (issu d\'une fuite, le plus souvent — c\'est rarement une vraie intrusion).',
                'Un compte rencontré récemment vous fait du chantage après un échange intime.',
            ],
            'steps' => [
                [
                    'key' => 'immediate',
                    'label' => 'Dans les 10 prochaines minutes',
                    'note'  => 'Trois règles, dans cet ordre.',
                    'actions' => [
                        ['title' => 'Ne payez pas. Jamais.',         'detail' => 'Le paiement ne stoppe rien — il prouve à l\'attaquant que vous êtes une cible solvable, et les demandes recommencent. Dans 99% des cas, l\'attaquant n\'a en fait rien d\'incriminant.'],
                        ['title' => 'Ne répondez pas',               'detail' => 'Aucun message, aucune négociation. Tout échange relance la pression. Le silence vous protège.'],
                        ['title' => 'Conservez les preuves',         'detail' => 'Captures d\'écran complètes (avec dates, identifiants, URL), liens vers les profils, numéros, adresses crypto si présentes. Ne supprimez rien : ça servira pour la plainte.'],
                    ],
                ],
                [
                    'key' => 'day',
                    'label' => 'Dans les 24 heures',
                    'note'  => 'Vous portez plainte. Vous parlez à quelqu\'un. Vous bloquez.',
                    'actions' => [
                        ['title' => 'Portez plainte',                   'detail' => 'Commissariat ou pre-plainte-en-ligne.gouv.fr. C\'est un délit (chantage : 5 ans de prison, 75 000 € d\'amende). Les enquêteurs savent traiter ces affaires, vous serez écouté·e sans jugement.'],
                        ['title' => 'Signalez sur Pharos',              'detail' => 'internet-signalement.gouv.fr. La plateforme officielle pour les contenus illicites en ligne.'],
                        ['title' => 'Bloquez l\'expéditeur',            'detail' => 'Sur tous les canaux. Plus aucune notification, plus aucun contact. Si la menace passe par un réseau social : signalez le compte à la plateforme.'],
                        ['title' => 'Parlez-en à quelqu\'un',           'detail' => 'Un proche, un médecin, une association. Ne restez pas seul·e avec ça. Si la victime est mineure : Net Écoute (3018) est gratuit, anonyme, et ils savent gérer.'],
                    ],
                ],
                [
                    'key' => 'after',
                    'label' => 'Reprendre la main durablement',
                    'note'  => 'On verrouille la vie privée et on n\'oublie pas que vous, vous n\'avez rien fait de mal.',
                    'actions' => [
                        ['title' => 'Vérifiez la confidentialité de vos comptes', 'detail' => 'Profils en privé, listes d\'amis cachées, anciennes photos passées en privé. Réduit la surface si jamais le contenu fuite.'],
                        ['title' => 'En cas de diffusion → demande de retrait',   'detail' => 'Pharos peut faire retirer. Sur les plateformes (Insta, TikTok, Facebook), la procédure « contenu intime non consenti » est rapide. Cybermalveillance vous accompagne.'],
                        ['title' => 'Soutien psychologique',                       'detail' => 'C\'est éprouvant. France Victimes (116 006), votre médecin traitant, ou un psychologue. Aucune honte à prendre soin de soi après ça.'],
                    ],
                ],
            ],
            'resources' => [
                ['label' => 'Pharos · Signalement',                            'url' => 'https://www.internet-signalement.gouv.fr',                                                      'kind' => 'official'],
                ['label' => 'Net Écoute · 3018 (mineurs)',                     'url' => 'https://www.e-enfance.org/numero-3018',                                                          'kind' => 'official'],
                ['label' => 'France Victimes · 116 006',                       'url' => 'https://www.france-victimes.fr',                                                                 'kind' => 'official'],
                ['label' => 'Cybermalveillance · Sextorsion',                  'url' => 'https://www.cybermalveillance.gouv.fr/tous-nos-contenus/fiches-reflexes/chantage-a-la-webcam-pretendue', 'kind' => 'official'],
                ['label' => 'Pré-plainte en ligne',                             'url' => 'https://www.pre-plainte-en-ligne.gouv.fr',                                                       'kind' => 'official'],
            ],
            'related' => ['compte-pirate'],
        ];
    }
}
