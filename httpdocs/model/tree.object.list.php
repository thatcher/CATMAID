<?php

include_once( 'errors.inc.php' );
include_once( 'db.pg.class.php' );
include_once( 'session.class.php' );
include_once( 'tools.inc.php' );
include_once( 'json.inc.php' );

$db =& getDB();
$ses =& getSession();

$pid = isset( $_REQUEST[ 'pid' ] ) ? intval( $_REQUEST[ 'pid' ] ) : 0;
$uid = $ses->isSessionValid() ? $ses->getId() : 0;

$parentid = isset( $_REQUEST[ 'parentid' ] ) ? intval($_REQUEST[ 'parentid' ]) : 0;
// extend it by giving a set of relationship types
// limit number of nodes retrievable
$maxnodes = 1000;

if ( $pid )
{
	if ( $uid )
	{
		// instances to display
		$nid = $db->getClassId( $pid, "neuron" );
    if(!$nid) { echo makeJSON( array( 'error' => 'Can not find "neuron" class for this project' ) ); return; }
		$skid = $db->getClassId( $pid, "skeleton" );
    if(!$skid) { echo makeJSON( array( 'error' => 'Can not find "skeleton" class for this project' ) ); return; }
		$gid = $db->getClassId( $pid, "group" );
    if(!$gid) { echo makeJSON( array( 'error' => 'Can not find "group" class for this project' ) ); return; }
		$rid = $db->getClassId( $pid, "root" );
    if(!$rid) { echo makeJSON( array( 'error' => 'Can not find "root" class for this project' ) ); return; }
		
		// relations
		$presyn_id = $db->getRelationId( $pid, "presynaptic_to" );
    if(!$presyn_id) { echo makeJSON( array( 'error' => 'Can not find "presynaptic_to" relation for this project' ) ); return; }
		$postsyn_id = $db->getRelationId( $pid, "postsynaptic_to" );
    if(!$postsyn_id) { echo makeJSON( array( 'error' => 'Can not find "postsynaptic_to" relation for this project' ) ); return; }
		$modid = $db->getRelationId( $pid, "model_of" );
    if(!$modid) { echo makeJSON( array( 'error' => 'Can not find "model_of" relation for this project' ) ); return; }
		$partof_id = $db->getRelationId( $pid, "part_of" );
    if(!$partof_id) { echo makeJSON( array( 'error' => 'Can not find "part_of" relation for this project' ) ); return; }

		if ( !$parentid ) {
			// retrieve the id of the root node for this project
			$res = $db->getResult('SELECT "ci"."id", "ci"."name" FROM "class_instance" AS "ci" 
			WHERE "ci"."project_id" = '.$pid.' AND "ci"."class_id" = '.$rid);
			
			$parid = !empty($res) ? $res[0]['id'] : 0;
			$parname = !empty($res) ? $res[0]['name'] : 'noname';
			
			$sOutput = '[';
			$ar = array(		
						'data' => array(
 							'title' => $parname,
						),
						'attr' => array('id' => 'node_'. $parid,
										'rel' => "root"),
						'state' => 'closed'								
						);
						
			$sOutput .= tv_node( $ar );
			$sOutput .= ']';
			echo $sOutput;
			return;
		}
		
		$res = $db->getResult('SELECT "ci"."id", "ci"."name", "ci"."class_id",
		"cici"."relation_id", "cici"."class_instance_b" AS "parent", "cl"."class_name"
		FROM "class_instance" AS "ci"
		INNER JOIN "class_instance_class_instance" AS "cici" 
			ON "ci"."id" = "cici"."class_instance_a" 
			INNER JOIN "class" AS "cl" 
				ON "ci"."class_id" = "cl"."id"
		WHERE "ci"."project_id" = '.$pid.' AND
		   "cici"."class_instance_b" = '.$parentid.' AND
		   ("cici"."relation_id" = '.$presyn_id.'
			OR "cici"."relation_id" = '.$postsyn_id.'
			OR "cici"."relation_id" = '.$modid.'
			OR "cici"."relation_id" = '.$partof_id.')
	  ORDER BY "ci"."edition_time" DESC
	  LIMIT '.$maxnodes);

		// loop through the array and generate children to return
		$sOutput = '[';
		$i = 0;
		foreach($res as $key => $ele) {
			$ar = array(		
						'data' => array(
 							'title' => $ele['name'],
						),
						'attr' => array('id' => 'node_'. $ele['id'],
						// replace whitespace because of tree object types
										'rel' => str_replace(" ", "", $ele['class_name'])),
						'state' => 'closed'								
						);
			if($i!=0)  { $sOutput .= ','; }
			$sOutput .= tv_node( $ar );
			$i++;
			
		};
		$sOutput .= ']';
		
		echo $sOutput;

	}
	else
		echo makeJSON( array( 'error' => 'You are not logged in currently.  Please log in to be able to retrieve the tree.' ) );
}
else
	echo makeJSON( array( 'error' => 'Project closed. Can not retrieve the tree.' ) );

?>