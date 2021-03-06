<?php

class EE_DOCKER {

	/**
	 * Check and Start or create container if not running.
	 *
	 * @param String $container Name of the container.
	 * @param String $command   Command to launch that container if needed.
	 *
	 * @return bool success.
	 */
	public static function boot_container( $container, $command = '' ) {
		$status = self::container_status( $container );
		if ( $status ) {
			if ( 'exited' === $status ) {
				return self::start_container( $container );
			} else {
				return true;
			}
		} else {
			return EE::exec( $command );
		}
	}

	public static function container_status( $container ) {
		$exec_command = 'which docker';
		exec( $exec_command, $out, $ret );
		EE::debug( 'COMMAND: ' . $exec_command );
		EE::debug( 'RETURN CODE: ' . $ret );
		if ( $ret ) {
			EE::error( 'Docker is not installed. Please install Docker to run EasyEngine.' );
		}
		$status = EE::launch( "docker inspect -f '{{.State.Running}}' $container" );
		if ( ! $status->return_code ) {
			if ( preg_match( '/true/', $status->stdout ) ) {
				return 'running';
			} else {
				return 'exited';
			}
		}

		return false;
	}

	/**
	 * Function to start the container if it exists but is not running.
	 *
	 * @param String $container Container to be started.
	 *
	 * @return bool success.
	 */
	public static function start_container( $container ) {
		return EE::exec( "docker start $container" );
	}

	/**
	 * Function to stop a container
	 *
	 * @param String $container Container to be stopped.
	 *
	 * @return bool success.
	 */
	public static function stop_container( $container ) {
		return EE::exec( "docker stop $container" );
	}

	/**
	 * Function to restart a container
	 *
	 * @param String $container Container to be restarted.
	 *
	 * @return bool success.
	 */
	public static function restart_container( $container ) {
		return EE::exec( "docker restart $container" );
	}

	/**
	 * Create docker network.
	 *
	 * @param String $name Name of the network to be created.
	 *
	 * @return bool success.
	 */
	public static function create_network( $name ) {
		return EE::exec( "docker network create $name --label=org.label-schema.vendor=\"EasyEngine\" " );
	}

	/**
	 * Connect to given docker network.
	 *
	 * @param String $name       Name of the network that has to be connected.
	 * @param String $connect_to Name of the network to which connection has to be established.
	 *
	 * @return bool success.
	 */
	public static function connect_network( $name, $connect_to ) {
		return EE::exec( "docker network connect $name $connect_to" );
	}

	/**
	 * Remove docker network.
	 *
	 * @param String $name Name of the network to be removed.
	 *
	 * @return bool success.
	 */
	public static function rm_network( $name ) {
		return EE::exec( "docker network rm $name" );
	}

	/**
	 * Disconnect docker network.
	 *
	 * @param String $name         Name of the network to be disconnected.
	 * @param String $connected_to Name of the network from which it has to be disconnected.
	 *
	 * @return bool success.
	 */
	public static function disconnect_network( $name, $connected_to ) {
		return EE::exec( "docker network disconnect $name $connected_to" );
	}


	/**
	 * Function to connect site network to appropriate containers.
	 */
	public static function connect_site_network_to( $site_name, $to_container ) {

		if ( self::connect_network( $site_name, $to_container ) ) {
			EE::success( "Site connected to $to_container." );
		} else {
			throw new Exception( "There was some error connecting to $to_container." );
		}
	}

	/**
	 * Function to disconnect site network from appropriate containers.
	 */
	public static function disconnect_site_network_from( $site_name, $from_container ) {

		if ( self::disconnect_network( $site_name, $from_container ) ) {
			EE::log( "[$site_name] Disconnected from Docker network of $from_container" );
		} else {
			EE::warning( "Error in disconnecting from Docker network of $from_container" );
		}
	}


	/**
	 * Function to boot the containers.
	 *
	 * @param String $dir      Path to docker-compose.yml.
	 * @param array  $services Services to bring up.
	 *
	 * @return bool success.
	 */
	public static function docker_compose_up( $dir, $services = [] ) {
		$chdir_return_code = chdir( $dir );
		if ( $chdir_return_code ) {
			if ( empty( $services ) ) {
				return EE::exec( 'docker-compose up -d' );
			} else {
				$all_services = implode( ' ', $services );

				return EE::exec( "docker-compose up -d $all_services" );
			}
		}

		return false;
	}

	/**
	 * Function to check if a network exists
	 *
	 * @param string $network Name/ID of network to check
	 *
	 * @return bool Network exists or not
	 */
	public static function docker_network_exists( string $network ) {
		return EE::exec( "docker network inspect $network" );
	}

	/**
	 * Function to destroy the containers.
	 *
	 * @param String $dir      Path to docker-compose.yml.
	 *
	 * @return bool success.
	 */
	public static function docker_compose_down( $dir ) {
		$chdir_return_code = chdir( $dir );
		if ( $chdir_return_code ) {

			return EE::exec( 'docker-compose down' );
		}

		return false;
	}

}
