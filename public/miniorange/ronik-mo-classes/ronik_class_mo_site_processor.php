<?php 

class RonikMoHelperSiteProcessor {
    private function siteAssigner(){
        // Request Site: Production && Staging
        $site_production_request = 'https://requests.together.nbcuni.com/';
        $site_staging_request = 'https://stage.requests.together.nbcuni.com/';
        $site_local_request = 'https://requests.together.nbcudev.local/';
        // Talentroom Site: Production && Staging
        $site_production_talentroom = 'https://talentroom.nbcuni.com/';
        $site_staging_talentroom = 'https://stage.talentroom.nbcuni.com/';
        $site_local_talentroom = 'https://talentroom.nbcudev.local/';
        // Together Site: Production && Staging
        $site_production_together = 'https://together.nbcuni.com/';
        $site_staging_together = 'https://stage.together.nbcuni.com/';
        $site_local_together = 'http://together.nbcudev.local/';
        // Blog ID for each site.
        $blog_id_together = 3;
        $blog_id_talent = 7;
        $blog_id_request = 6;
        // Route Domain for each site.
        $site_production_route_domain = ".nbcuni.com";
        $site_staging_route_domain = ".nbcuni.com";
        $site_local_route_domain = ".nbcudev.local";
        return [ $site_production_request, $site_staging_request, $site_local_request, $site_production_talentroom , $site_staging_talentroom, $site_local_talentroom , $site_production_together, $site_staging_together, $site_local_together, $blog_id_together , $blog_id_talent , $blog_id_request , $site_production_route_domain , $site_staging_route_domain , $site_local_route_domain ]; // Return multiple variables as an array
    }

    private function getEnvironment($server_name) {
        if (stristr($server_name, 'local')) return 'local';
        if (stristr($server_name, 'stage')) return 'stage';
        return 'production';
    }

    public function siteMapping($siteTarget){
        [
            $site_production_request, 
            $site_staging_request, 
            $site_local_request, 
            $site_production_talentroom, 
            $site_staging_talentroom, 
            $site_local_talentroom, 
            $site_production_together, 
            $site_staging_together, 
            $site_local_together, 
            $blog_id_together, 
            $blog_id_talent, 
            $blog_id_request,
            $site_production_route_domain, 
            $site_staging_route_domain, 
            $site_local_route_domain
        ] = $this->siteAssigner();
        $environment = $this->getEnvironment($_SERVER['SERVER_NAME']);
        $site_mapping = [
            'production' => [ 'route_domain' => $site_production_route_domain , 'request_id' => $blog_id_request, 'request' => $site_production_request, 'talentroom_id' => $blog_id_talent, 'together_id' => $blog_id_together, 'talentroom' => $site_production_talentroom, 'together' => $site_production_together],
            'stage' => [ 'route_domain' => $site_staging_route_domain , 'request_id' => $blog_id_request, 'request' => $site_staging_request, 'talentroom_id' => $blog_id_talent, 'together_id' => $blog_id_together, 'talentroom' => $site_staging_talentroom, 'together' => $site_staging_together],
            'local' => [ 'route_domain' => $site_local_route_domain , 'request_id' => $blog_id_request, 'request' => $site_local_request, 'talentroom_id' => $blog_id_talent, 'together_id' => $blog_id_together, 'talentroom' => $site_local_talentroom, 'together' => $site_local_together]
        ];
        $data_array = [];

        $data_array['site_url'] = $site_mapping[$environment][$siteTarget] ?? null;
        $data_array['site_url_route_domain'] = $site_mapping[$environment]['route_domain'] ?? null;
        $data_array['environment'] = $environment;
        $data_array['site_mapping'] = $site_mapping;
        return $data_array; // Return multiple variables as an array
    }

    public function urlSsoGenerator($siteTarget=false, $queryParameters){
        $mo_helper_site_processor_data = $this->siteMapping('together');
        if(!$siteTarget){
            $siteTarget = 'together';
        }
        $site_together_sso_login = $mo_helper_site_processor_data['site_url'].'saml_user_login_custom?'.$siteTarget.'=1';
        if (isset($queryParameters['r']) && $queryParameters['r']) {
            $site_together_sso_login .= '&' . http_build_query(['r' => $queryParameters['r']]); 
        }
        return $site_together_sso_login;
    }

}  

