<?php

namespace App\Http\Controllers;

use App\LabelPrintProfile;
use App\Services\ZebraLabelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LabelZebraController extends Controller
{
    protected $zebra;

    public function __construct(ZebraLabelService $zebra)
    {
        $this->zebra = $zebra;
    }

    public function product(Request $request, $variationId)
    {
        $this->authorizeLabels();

        try {
            $priceGroupId = $request->filled('price_group_id') ? (int) $request->input('price_group_id') : null;
            $product = $this->zebra->productPayload($this->businessId($request), (int) $variationId, $priceGroupId);

            return response()->json(['success' => true, 'product' => $product]);
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), 422);
        } catch (\Throwable $e) {
            \Log::error('Zebra label product lookup failed: '.$e->getMessage());

            return $this->fail('The selected product could not be loaded.', 500);
        }
    }

    public function store(Request $request)
    {
        $this->authorizeLabels();

        try {
            $businessId = $this->businessId($request);
            $name = $this->zebra->profileName($request->input('name'));
            if ($request->boolean('auto_name')) {
                $name = $this->uniqueName($businessId, $name);
            } else {
                $this->assertUniqueName($businessId, $name);
            }

            $layout = $this->zebra->layoutFromRequest($request->input('layout', []));
            $profile = LabelPrintProfile::create(array_merge($layout, [
                'business_id' => $businessId,
                'name' => $name,
                'created_by' => $request->session()->get('user.id'),
                'is_default' => false,
                'columns' => 3,
            ]));

            return $this->saved('Label profile created.', $businessId, $profile);
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), 422);
        } catch (\Throwable $e) {
            \Log::error('Zebra label profile create failed: '.$e->getMessage());

            return $this->fail('The label profile could not be saved.', 500);
        }
    }

    public function update(Request $request, $id)
    {
        $this->authorizeLabels();

        try {
            $businessId = $this->businessId($request);
            $profile = $this->findProfile($businessId, (int) $id);
            $name = $this->zebra->profileName($request->input('name', $profile->name));
            $this->assertUniqueName($businessId, $name, $profile->id);
            $layout = $this->zebra->layoutFromRequest($request->input('layout', []));

            $profile->fill(array_merge($layout, [
                'name' => $name,
                'columns' => 3,
            ]));
            $profile->save();

            return $this->saved('Layout saved.', $businessId, $profile);
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), 422);
        } catch (\Throwable $e) {
            \Log::error('Zebra label profile update failed: '.$e->getMessage());

            return $this->fail('The label profile could not be saved.', 500);
        }
    }

    public function rename(Request $request, $id)
    {
        $this->authorizeLabels();

        try {
            $businessId = $this->businessId($request);
            $profile = $this->findProfile($businessId, (int) $id);
            $name = $this->zebra->profileName($request->input('name'));
            $this->assertUniqueName($businessId, $name, $profile->id);
            $profile->name = $name;
            $profile->save();

            return $this->saved('Profile renamed.', $businessId, $profile);
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), 422);
        } catch (\Throwable $e) {
            \Log::error('Zebra label profile rename failed: '.$e->getMessage());

            return $this->fail('The label profile could not be renamed.', 500);
        }
    }

    public function duplicate(Request $request, $id)
    {
        $this->authorizeLabels();

        try {
            $businessId = $this->businessId($request);
            $profile = $this->findProfile($businessId, (int) $id);
            $copy = $profile->replicate();
            $copy->name = $this->uniqueName($businessId, $profile->name.' copy');
            $copy->is_default = false;
            $copy->created_by = $request->session()->get('user.id');
            $copy->save();

            return $this->saved('Profile duplicated.', $businessId, $copy);
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), 422);
        } catch (\Throwable $e) {
            \Log::error('Zebra label profile duplicate failed: '.$e->getMessage());

            return $this->fail('The label profile could not be duplicated.', 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $this->authorizeLabels();

        try {
            $businessId = $this->businessId($request);
            $profile = $this->findProfile($businessId, (int) $id);
            $wasDefault = (bool) $profile->is_default;

            DB::transaction(function () use ($profile, $businessId, $wasDefault, $request) {
                $profile->delete();
                $remaining = LabelPrintProfile::where('business_id', $businessId)->orderBy('id')->get();

                if ($remaining->isEmpty()) {
                    LabelPrintProfile::create(array_merge(LabelPrintProfile::defaultAttributes(), [
                        'business_id' => $businessId,
                        'created_by' => $request->session()->get('user.id'),
                        'is_default' => true,
                    ]));
                } elseif ($wasDefault) {
                    $next = $remaining->first();
                    LabelPrintProfile::where('business_id', $businessId)->update(['is_default' => false]);
                    $next->is_default = true;
                    $next->save();
                }
            });

            $profiles = LabelPrintProfile::where('business_id', $businessId)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Profile deleted.',
                'profiles' => $profiles,
                'profile' => $profiles->firstWhere('is_default', true) ?? $profiles->first(),
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), 422);
        } catch (\Throwable $e) {
            \Log::error('Zebra label profile delete failed: '.$e->getMessage());

            return $this->fail('The label profile could not be deleted.', 500);
        }
    }

    public function setDefault(Request $request, $id)
    {
        $this->authorizeLabels();

        try {
            $businessId = $this->businessId($request);
            $profile = $this->findProfile($businessId, (int) $id);

            DB::transaction(function () use ($businessId, $profile) {
                LabelPrintProfile::where('business_id', $businessId)->update(['is_default' => false]);
                $profile->is_default = true;
                $profile->save();
            });

            return $this->saved('Default profile updated.', $businessId, $profile->fresh());
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), 422);
        } catch (\Throwable $e) {
            \Log::error('Zebra label default profile failed: '.$e->getMessage());

            return $this->fail('The default profile could not be updated.', 500);
        }
    }

    public function zpl(Request $request)
    {
        $this->authorizeLabels();

        try {
            $businessId = $this->businessId($request);
            $rows = $this->zebra->rowsFromRequest($request->input('quantity'));
            $priceGroupId = $request->filled('price_group_id') ? (int) $request->input('price_group_id') : null;
            $product = $this->zebra->productPayload($businessId, (int) $request->input('variation_id'), $priceGroupId);

            if (! empty($product['barcode_missing']) || $product['barcode'] === '') {
                throw new InvalidArgumentException('Barcode is missing for this product.');
            }

            $layout = $this->zebra->layoutFromRequest($request->input('layout', []));
            $zpl = $this->zebra->buildZpl(
                $layout,
                $product['barcode'],
                $product['price'],
                (string) $layout['vertical_text'],
                $rows,
                (string) ($product['name'] ?? '')
            );
            $labels = $rows * 3;

            return response()->json([
                'success' => true,
                'message' => 'Sending '.$rows.' '.($rows === 1 ? 'row' : 'rows').' ('.$labels.' labels) to '.$layout['printer_name'].'.',
                'zpl' => $zpl,
                'printer_name' => $layout['printer_name'],
                'rows' => $rows,
                'labels' => $labels,
                'product' => $product,
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), 422);
        } catch (\Throwable $e) {
            \Log::error('Zebra label ZPL failed: '.$e->getMessage());

            return $this->fail('Unable to prepare the label. Check the product and layout, then try again.', 500);
        }
    }

    private function authorizeLabels(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->can('product.view')) {
            abort(response()->json([
                'success' => false,
                'message' => 'You are not allowed to print labels.',
            ], 403));
        }
    }

    private function businessId(Request $request): int
    {
        $id = (int) $request->session()->get('user.business_id');
        if ($id < 1) {
            abort(response()->json([
                'success' => false,
                'message' => 'Your session has expired. Sign in again.',
            ], 401));
        }

        return $id;
    }

    private function findProfile(int $businessId, int $id): LabelPrintProfile
    {
        $profile = LabelPrintProfile::where('business_id', $businessId)->where('id', $id)->first();
        if (! $profile) {
            throw new InvalidArgumentException('The selected label profile could not be found.');
        }

        return $profile;
    }

    private function assertUniqueName(int $businessId, string $name, ?int $ignoreId = null): void
    {
        $query = LabelPrintProfile::where('business_id', $businessId)->where('name', $name);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }
        if ($query->exists()) {
            throw new InvalidArgumentException('A label profile with this name already exists.');
        }
    }

    private function uniqueName(int $businessId, string $base): string
    {
        $name = mb_substr($base, 0, 80);
        $candidate = $name;
        $i = 2;
        while (LabelPrintProfile::where('business_id', $businessId)->where('name', $candidate)->exists()) {
            $suffix = ' '.$i;
            $candidate = mb_substr($name, 0, 80 - mb_strlen($suffix)).$suffix;
            $i++;
        }

        return $candidate;
    }

    private function saved(string $message, int $businessId, LabelPrintProfile $profile)
    {
        $profiles = LabelPrintProfile::where('business_id', $businessId)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => $message,
            'profiles' => $profiles,
            'profile' => $profile,
        ]);
    }

    private function fail(string $message, int $status)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
