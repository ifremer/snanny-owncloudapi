import rdflib
from rdflib.parser import Parser
from rdflib.serializer import Serializer

graph = rdflib.Graph()

my_url = [['http://vocab.nerc.ac.uk/collection/S02/current/','s02'],['http://vocab.nerc.ac.uk/collection/S06/current/','s06'],['http://vocab.nerc.ac.uk/collection/S07/current/','s07'],['http://vocab.nerc.ac.uk/collection/S26/current/','s26'],['http://vocab.nerc.ac.uk/collection/S27/current/','s27'],['http://vocab.nerc.ac.uk/collection/P06/current/','p06'],['http://vocab.nerc.ac.uk/collection/P07/current/','p07'],['http://vocab.nerc.ac.uk/collection/P01/current/','p01']]

size_my_url = len(my_url)

print('lancement du programme de convertissement')

i = 7
j = size_my_url
while i < j :
	name_fichier = my_url[i][1]+'.jsonld'
	print('traitement du fichier : '+name_fichier+' en cours')
	graph.parse(my_url[i][0], format='application/rdf+xml')
	fichier = open(name_fichier, "w")
	fichier.write(graph.serialize(format='json-ld', indent=2))
	fichier.close
	print('traitement du fichier : '+name_fichier+' fini')
	i = i + 1

print('fin du programme de convertissement')