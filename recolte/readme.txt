Scripts de récolte :
	- index.php
		pour récupérer toutes les url des ressources œuvres
	- supp_ss_vign.php
		pour supprimer les url d'œuvres dont la vignette n'est pas accessible (peut nécessiter plusieurs passes, voire se repositionner après les éléments déjà traités)
	- supp_notgood.php
		pour supprimer les url indiquées comme artworks dans la dbpedia en et qui n'en sont pas
	- transf.php
		pour transférer les données de la base de récolte vers la base principale qui est écrasée
Lancer d'abord index.php puis les 2 autres et enfin transf.php
