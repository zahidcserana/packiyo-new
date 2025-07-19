<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreRequest;
use App\Http\Requests\User\UpdateRequest;
use App\Http\Resources\UserTableResource;
use App\Models\Customer;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\{Facades\View};

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class);
    }

    public function index()
    {
        return view('users.index', [
            'datatableOrder' => app('editColumn')->getDatatableOrder('users')
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function dataTable(Request $request): JsonResponse
    {
        $customer = app('user')->getSelectedCustomers();
        $customers = $customer->pluck('id')->toArray();

        $tableColumns = $request->get('columns');
        $columnOrder = $request->get('order');
        $sortColumnName = 'users.id';
        $sortDirection = 'desc';

        if (!empty($columnOrder)) {
            $sortColumnName = $tableColumns[$columnOrder[0]['column']]['name'];
            $sortDirection = $columnOrder[0]['dir'];
        }

        $usersCollection = User::leftJoin('contact_informations',
            static function (JoinClause $joinClause) {
                $joinClause->where('contact_informations.object_type', User::class)
                    ->on('contact_informations.object_id', '=', 'users.id');
            })
            ->leftJoin('user_roles', 'users.user_role_id', '=', 'user_roles.id')
            ->leftJoin('customer_user', 'customer_user.user_id', '=', 'users.id')
            ->leftJoin('customers', 'customer_user.customer_id', '=', 'customers.id')
            ->where(static function (Builder $query) use ($customers) {
                $query->whereIn('customers.id', $customers)
                    ->orWhere('users.user_role_id', UserRole::ROLE_ADMINISTRATOR);
            })
            ->select('users.*')
            ->groupBy('users.id')
            ->orderBy($sortColumnName, $sortDirection);

        $term = $request->get('search')['value'];

        if ($term) {
            // TODO: sanitize term
            $term = $term . '%';

            $usersCollection->where(function ($q) use ($term) {
                $q->where('users.email', 'like', $term)
                    ->orWhereHas('contactInformation', function ($query) use ($term) {
                        $query->where('name', 'like', $term);
                    })
                    ->orWhereHas('role', function ($query) use ($term) {
                        $query->where('name', 'like', $term);
                    });
            });
        }
        if ($request->get('length') && ((int) $request->get('length')) !== -1) {
            $usersCollection = $usersCollection->skip($request->get('start'))->limit($request->get('length'));
        }

        $users = $usersCollection->where('system_user', false)->get();

        $visibleFields = app('editColumn')->getVisibleFields('users');

        return response()->json([
            'data' => UserTableResource::collection($users),
            'visibleFields' => $visibleFields,
            'recordsTotal' => PHP_INT_MAX,
            'recordsFiltered' => PHP_INT_MAX,
        ]);
    }

    /**
     * @param User $user
     * @return \Illuminate\Contracts\View\View
     */
    public function getCreateUserModal(User $user): \Illuminate\Contracts\View\View
    {
        $roles = UserRole::all();
        return View::make('users.create', compact('user', 'roles'));
    }

    /**
     * @param User|null $user
     * @return \Illuminate\Contracts\View\View
     */
    public function getEditUserModal(User $user = null): \Illuminate\Contracts\View\View
    {
        $roles = UserRole::all();
        return View::make('users.edit', compact('user', 'roles'));
    }

    /**
     * @param StoreRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $this->authorize('create', auth()->user());

        app('user')->store($request);

        return response()->json([
            'success' => true,
            'message' => __('User successfully created.'),
        ]);
    }

    /**
     * @param User $user
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function disable(User $user): JsonResponse
    {
        $this->authorize('disable', auth()->user());

        app('user')->disable($user);

        return response()->json([
            'success' => true,
            'message' => __('User successfully disabled.'),
        ]);
    }

    /**
     * @param User $user
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function enable(User $user): JsonResponse
    {
        $this->authorize('enable', auth()->user());

        app('user')->enable($user);

        return response()->json([
            'success' => true,
            'message' => __('User successfully enabled.'),
        ]);
    }

    /**
     * @param UpdateRequest $request
     * @param User $user
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(UpdateRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', auth()->user());

        app('user')->update($request, $user);

        return response()->json([
            'success' => true,
            'message' => __('User successfully updated.'),
        ]);
    }

    /**
     * @param User $user
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', auth()->user());

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => __('User successfully deleted.'),
        ]);
    }

    /**
     * @param Request $request
     * @param Customer $customer
     * @return RedirectResponse
     */
    public function setSessionCustomer(Request $request, Customer $customer): RedirectResponse
    {
        $redirect = $request->query('redirect');

        app('user')->setSessionCustomer($customer);

        return $redirect ? redirect()->to($redirect) : redirect()->back();
    }

    /**
     * @return RedirectResponse
     */
    public function removeSessionCustomer(): RedirectResponse
    {
        app('user')->removeSessionCustomer();

        return redirect()->back();
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getCustomers(Request $request)
    {
        return app('user')->filterCustomers($request);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function get3plCustomers(Request $request)
    {
        return app('user')->filterCustomers($request, true);
    }
}
