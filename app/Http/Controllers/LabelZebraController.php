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

    public function updateBarcode(Request $request, $variationId)
    {
        $this->authorizeLabels();
        if (! auth()->user()->can('product.update')) {
            return $this->fail('You are not allowed to update this product.', 403);
        }

        try {
            $product = $this->zebra->updateBarcode(
                $this->businessId($request),
                (int) $variationId,
                (string) $request->input('barcode', '')
            );

            return response()->json([
                'success' => true,
                'message' => 'Barcode / SKU updated successfully.',
                'product' => $product,
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), 422);
        } catch (\Throwable $e) {
            \Log::error('Zebra barcode update failed: '.$e->getMessage());

            return $this->fail('Unable to update Barcode / SKU. Please try again.', 500);
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
        return $this->composeTemporaryPrint($request);
    }

    public function temporaryPrint(Request $request)
    {
        return $this->composeTemporaryPrint($request);
    }

    /**
     * Build ZPL from a copy of the submitted layout.
     * This path never reads the layout back onto a LabelPrintProfile and never saves one.
     */
    private function composeTemporaryPrint(Request $request)
    {
        $this->authorizeLabels();

        try {
            $businessId = $this->businessId($request);
            $profileId = (int) $request->input('profile_id', 0);
            $before = $this->profileSnapshot($businessId, $profileId);
            $rows = $this->zebra->rowsFromRequest($request->input('quantity'));
            $layout = $this->zebra->layoutFromRequest($request->input('layout', []));
            if ($request->boolean('test')) {
                $job = $this->zebra->buildTestJob($layout, $rows);
                $product = null;
            } else {
                $priceGroupId = $request->filled('price_group_id') ? (int) $request->input('price_group_id') : null;
                $product = $this->zebra->productPayload($businessId, (int) $request->input('variation_id'), $priceGroupId);

                if (! empty($product['barcode_missing']) || $product['barcode'] === '') {
                    throw new InvalidArgumentException('Barcode is missing for this product.');
                }

                $job = $this->zebra->buildJob(
                    $layout,
                    $product['barcode'],
                    $product['price'],
                    (string) $layout['vertical_text'],
                    $rows,
                    (string) ($product['name'] ?? '')
                );
            }
            $this->assertProfileUntouched($before, $businessId, $profileId);
            $labels = $rows * 3;
            $noun = $rows === 1 ? 'row' : 'rows';
            $message = $request->boolean('test')
                ? 'Test print: '.$rows.' '.$noun.' ('.$labels.' labels) on '.$layout['printer_name'].'. The saved label was not changed.'
                : 'Sending '.$rows.' '.$noun.' ('.$labels.' labels) to '.$layout['printer_name'].'. The saved label was not changed.';

            return response()->json([
                'success' => true,
                'temporary' => true,
                'persisted' => false,
                'message' => $message,
                'zpl' => $job['zpl'],
                'warnings' => $job['warnings'],
                'media' => $job['media'],
                'printer_name' => $layout['printer_name'],
                'rows' => $rows,
                'labels' => $labels,
                'product' => $product,
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->fail($e->getMessage(), 422);
        } catch (\Throwable $e) {
            \Log::error('Zebra temporary print failed: '.$e->getMessage());

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

    private function profileSnapshot(int $businessId, int $profileId): ?array
    {
        if ($profileId < 1) {
            return null;
        }

        $profile = LabelPrintProfile::where('business_id', $businessId)->where('id', $profileId)->first();
        if (! $profile) {
            throw new InvalidArgumentException('The selected label profile could not be found.');
        }

        return $profile->getAttributes();
    }

    private function assertProfileUntouched(?array $before, int $businessId, int $profileId): void
    {
        if ($before === null) {
            return;
        }

        $profile = LabelPrintProfile::where('business_id', $businessId)->where('id', $profileId)->first();
        $after = $profile ? $profile->getAttributes() : null;
        if ($after != $before) {
            throw new \RuntimeException('Temporary print changed the saved label.');
        }
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
