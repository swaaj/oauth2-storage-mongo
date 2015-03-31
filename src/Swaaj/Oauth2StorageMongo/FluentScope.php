<?php
/**
 * Fluent storage implementation for an OAuth 2.0 Scope
 *
 * @package   lucadegasperi/oauth2-server-laravel
 * @author    Luca Degasperi <luca@lucadegasperi.com>
 * @copyright Copyright (c) Luca Degasperi
 * @licence   http://mit-license.org/
 * @link      https://github.com/lucadegasperi/oauth2-server-laravel
 */

namespace Swaaj\Oauth2StorageMongo;

use Illuminate\Database\ConnectionResolverInterface as Resolver;
use League\OAuth2\Server\Storage\ScopeInterface;
use League\OAuth2\Server\Entity\ScopeEntity;

class FluentScope extends FluentAdapter implements ScopeInterface
{
    protected $limitClientsToScopes = false;

    protected $limitScopesToGrants = false;

    public function __construct(Resolver $resolver, $limitClientsToScopes = false, $limitScopesToGrants = false)
    {
        parent::__construct($resolver);
        $this->limitClientsToScopes = $limitClientsToScopes;
        $this->limitScopesToGrants = $limitScopesToGrants;
    }

    public function limitClientsToScopes($limit = false)
    {
        $this->limitClientsToScopes = $limit;
    }

    public function limitScopesToGrants($limit = false)
    {
        $this->limitScopesToGrants = $limit;
    }

    public function areClientsLimitedToScopes()
    {
        return $this->limitClientsToScopes;
    }

    public function areScopesLimitedToGrants()
    {
        return $this->limitScopesToGrants;
    }


    /**
     * Return information about a scope
     *
     * Example SQL query:
     *
     * <code>
     * SELECT * FROM oauth_scopes WHERE scope = :scope
     * </code>
     *
     * @param  string     $scope     The scope
     * @param  string     $grantType The grant type used in the request (default = "null")
     * @param  string     $clientId  The client id used for the request (default = "null")
     * @return \League\OAuth2\Server\Entity\ScopeEntity|null If the scope doesn't exist return false
     */
    public function get($scope, $grantType = null, $clientId = null)
    {
         $query = $this->getConnection()->table('oauth_scopes')
                    ->where('id', $scope);

        if ($this->limitClientsToScopes === true and ! is_null($clientId)) {
            $allowedScopeIds = $this->getConnection()->table('oauth_client_scopes')
                   ->where('client_id', $clientId)
                   ->pluck('scope_id');

            $query = $query->whereIn('client_id', $allowedScopeIds);
        }

        if ($this->limitScopesToGrants === true and ! is_null($grantType)) {
            $allowedGrantIds = $this->getConnection()->table('oauth_grants')
                   ->where('id', $grantType)
                   ->pluck('id');

            $allowedScopeIds = $this->getConnection()->table('oauth_grant_scopes')
                   ->whereIn('grant_id', $allowedGrantIds)
                   ->pluck('scope_id');

            $query = $query->whereIn('id', $allowedScopeIds);
        }

        $result = $query->first();

        if (is_null($result)) {
            return null;
        }

        $scope = new ScopeEntity($this->getServer());
        $scope->hydrate([
            'id' => $result['id'],
            'description' => $result['description']
        ]);
        return $scope;
    }
}
