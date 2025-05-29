@extends('layout')

@section('extraHead')
    <link rel="stylesheet" href="{{ asset('css/pages/reglement.css') }}">
@endsection

@section('content')
    <div class="standart-page">
        <div class="content">
            <div class="text">
                <p class="title">Règlement :</p>

                <p class="sectionTitle">Préambule :</p>
                <ul>
                    <li>
                        <p class="paragraph">Tout joueur se connectant à l'infrastructure d'Arffornia est considéré comme
                            approuvant le règlement.</p>
                    </li>
                    <li>
                        <p class="paragraph">Le règlement peut être modifié dans le futur, les modifications vous seront
                            transmises via Discord ou l'onglet "news".</p>
                    </li>
                </ul>

                <p class="sectionTitle">I - Généralités :</p>
                <ul>
                    <li>
                        <p class="paragraph">Arffornia est avant tout un serveur Minecraft de build où l’on fait sa propre
                            survie dans un environnement convivial. Les joueurs sont tenus de respecter cet esprit
                            communautaire..</p>
                    </li>
                    <li>
                        <p class="paragraph">Chaque joueur est responsable du compte qu'il utilise, que ce soit le titulaire
                            ou non.</p>
                    </li>
                    <li>
                        <p class="paragraph">L'usage d'un double compte ou le prêt d'un compte Minecraft est interdit.</p>
                    </li>
                    <li>
                        <p class="paragraph">La vente de services, stuff, argent ... par d'autres moyens que ceux mis en
                            place (site web, argent du jeu) est interdite.</p>
                    </li>
                    <li>
                        <p class="paragraph">Toutes formes d'arnaques sont prohibées.</p>
                    </li>
                    <li>
                        <p class="paragraph">Le don de stuff (en dehors de votre team) est interdit.</p>
                    </li>
                    <li>
                        <p class="paragraph">En cas de litige avec un autre joueur ou un membre du personnel, merci d'en
                            informer le salon de support Discord en fournissant des preuves (vidéos ou captures d'écran).
                        </p>
                    </li>
                    <li>
                        <p class="paragraph">Ce règlement n'est pas exhaustif, des sanctions peuvent être appliquées en cas
                            de comportement inapproprié non répertorié.</p>
                    </li>
                </ul>

                <p class="sectionTitle">II - In Game :</p>
                <ul>
                    <li>
                        <p class="paragraph">La dégradation de build, l'appropriation ou le déplacement d’un bien d’un autre
                            joueur sans son consentement y est strictement interdit.</p>
                    </li>
                    <li>
                        <p class="paragraph">La pose de claim, la dégradation des chunks autour d'une base dans le but de
                            nuire est interdite.</p>
                    </li>
                    <li>
                        <p class="paragraph">Les bases des joueurs inactifs et peu avancées depuis plus de 6 mois peuvent
                            être détruites par le staff si celles-ci gênent celles d'un autre.</p>
                    </li>
                    <li>
                        <p class="paragraph">Toutes formes de machines, use-bugs ayant pour but de ralentir / crasher le
                            serveur sont sévèrement sanctionnées.</p>
                    </li>
                    <li>
                        <p class="paragraph">Toutes formes de triche sont interdites.</p>
                    </li>
                    <li>
                        <p class="paragraph">Il est de la responsabilité de chaque joueur de signaler toute activité de
                            triche; toute complicité avec un tricheur est passible de sanction.</p>
                    </li>
                    <li>
                        <p class="paragraph">L’utilisation d’un système tiers quelconque est interdite : macro, vm, proxy,
                            vpn, etc...</p>
                    </li>
                    <li>
                        <p class="paragraph">La modification du launcher ou d’un autre que celui officiel d’Arffornia pour
                            se connecter au serveur est interdite.</p>
                    </li>
                    <li>
                        <p class="paragraph">Toutes formes d'anti AFK, d'auto click sont interdites (cela comprend l'objet
                            bloquant un bouton).</p>
                    </li>
                    <li>
                        <p class="paragraph">Un comportement inapproprié envers les autres joueurs peut entraîner des
                            sanctions; veillez à maintenir une atmosphère respectueuse et conviviale.</p>
                    </li>
                </ul>

                <p class="sectionTitle">III - Optimisation :</p>
                <ul>
                    <li>
                        <p class="paragraph">Arffornia étant un serveur avec de nombreux mods n'étant pas forcément très
                            optimisés, nous vous demanderons de bien faire attention à optimiser vos bases, farm, etc..
                            Voici une liste non exhaustive des points à respecter.</p>
                    </li>
                    <ul>
                        <li>
                            <p class="paragraph">Auto-coupure d'une machine (pour ne pas qu'elle tourne pour rien).</p>
                        </li>
                        <li>
                            <p class="paragraph">Gestion efficace des déchets (destruction dans une poubelle, lave, ...). Il
                                est interdit de simplement dropper les items par terre.</p>
                        </li>

                    </ul>
                    <li>
                        <p class="paragraph">Un membre du staff peut vous avertir qu'une machine/base cause trop de lags
                            puis vous sanctionner (ex : destruction de la machine) si aucune optimisation sérieuse n'est
                            demandée.</p>
                    </li>
                    <li>
                        <p class="paragraph">Il est fortement recommandé d'installer un switch on/off sur vos machines /
                            base.</p>
                    </li>
                    <li>
                        <p class="paragraph">Vous avez le droit à un maximum de 20 entités par chunk. Dépasser ce nombre,
                            vous pouvez être sanctionné.</p>
                    </li>
                </ul>

                <p class="sectionTitle">IV - Chat :</p>
                <ul>
                    <li>
                        <p class="paragraph">Le spam, flood, majuscules abusives sont interdits.</p>
                    </li>
                    <li>
                        <p class="paragraph">Toutes formes de haine, insulte, discrimination sont fortement sanctionnées.
                        </p>
                    </li>
                    <li>
                        <p class="paragraph">Les messages se plaignant du serveur ne sont pas les bienvenus. Vous pouvez
                            donner un retour sur le salon support du Discord.</p>
                    </li>
                    <li>
                        <p class="paragraph">La pub pour des services autres que liés au serveur est interdite.</p>
                    </li>
                    <li>
                        <p class="paragraph">La demande de don est interdite.</p>
                    </li>
                    <li>
                        <p class="paragraph">Merci de faire attention à votre orthographe dans le chat.</p>
                    </li>
                </ul>

                <p class="sectionTitle">V - Boutique :</p>
                <ul>
                    <li>
                        <p class="paragraph">Tout achat dans la boutique n'est pas remboursable.</p>
                    </li>
                    <li>
                        <p class="paragraph">En cas de problème via un achat (ex : si vous ne le recevez pas), merci de
                            contacter le support.</p>
                    </li>
                    <li>
                        <p class="paragraph">La vente de code promo pour la boutique est interdite.</p>
                    </li>
                </ul>

                <p class="sectionTitle">VI - Système de progression :</p>
                <ul>
                    <li>
                        <p class="paragraph">La demande de modification de certaines interconnexions ou items requis pour un
                            jalon est la bienvenue si cela permet d'améliorer le système / l'équilibrage.</p>
                    </li>
                    <li>
                        <p class="paragraph">Les joueurs ont le droit d'utiliser les items générés naturellement même si
                            ceux-ci ne sont pas débloqués par le joueur.</p>
                    </li>
                    <li>
                        <p class="paragraph">Les prix de vente / location d'item à un joueur ne l'ayant pas débloqué doivent
                            être ajustés pour garder un équilibre entre le palier du joueur et le palier de déblocage de
                            l'item.</p>
                    </li>

                </ul>

                <p class="sectionTitle">VII - Bugs :</p>
                <ul>
                    <li>
                        <p class="paragraph">Si un joueur découvre un bug, il doit en informer rapidement un Administrateur
                            ou un Développeur du staff en privé.</p>
                    </li>
                    <li>
                        <p class="paragraph">Seuls les bugs listés ci-dessous sont autorisés, les autres sont interdits :
                        </p>
                    </li>
                    <ul>
                        <li>
                            <p class="paragraph">Destruction de la bedrock.</p>
                        </li>
                    </ul>
                </ul>


            </div>
        </div>

    </div>
@endsection
@section('script')
@endsection
