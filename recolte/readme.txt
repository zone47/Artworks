Scripts de r�colte :
	- index.php
		pour r�cup�rer toutes les url des ressources �uvres
	- supp_ss_vign.php
		pour supprimer les url d'�uvres dont la vignette n'est pas accessible (peut n�cessiter plusieurs passes, voire se repositionner apr�s les �l�ments d�j� trait�s)
	- supp_notgood.php
		pour supprimer les url indiqu�es comme artworks dans la dbpedia en et qui n'en sont pas
	- transf.php
		pour transf�rer les donn�es de la base de r�colte vers la base principale qui est �cras�e
Lancer d'abord index.php puis les 2 autres et enfin transf.php
