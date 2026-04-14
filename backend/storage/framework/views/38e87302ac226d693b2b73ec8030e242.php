<?php if (isset($component)) { $__componentOriginal166a02a7c5ef5a9331faf66fa665c256 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-panels::components.page.index','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-panels::page'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <section class="">
        <header class="flex items-center gap-x-3 overflow-hidden py-4">
            <div class="grid flex-1 gap-y-1">
                <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    <?php echo e(__('themes::themes.primary_color')); ?>

                </h3>

                <p class="fi-section-header-description text-sm text-gray-500 dark:text-gray-400">
                    <?php echo e(__('themes::themes.select_base_color')); ?>

                </p>
            </div>
        </header>

        <div class="flex items-center gap-4 border-t py-6">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->getCurrentTheme() instanceof \Hasnayeen\Themes\Contracts\HasChangeableColor): ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $this->getColors(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $name => $color): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <button
                        wire:click="setColor('<?php echo e($name); ?>')"
                        class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                            'w-4 h-4 rounded-full',
                            'ring p-1 border' => $this->getColor() === $name,
                        ]); ?>"
                        style="background-color: rgb(<?php echo e($color[500]); ?>);">
                    </button>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <div class="flex items-center space-x-4 rtl:space-x-reverse">
                    <input type="color" id="custom" name="custom" class="w-4 h-4" wire:change="setColor($event.target.value)" value="" />
                    <label for="custom"><?php echo e(__('themes::themes.custom')); ?></label>
                </div>
            <?php else: ?>
                <p class="text-gray-700 dark:text-gray-400"><?php echo e(__('themes::themes.no_changing_primary_color')); ?></p>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </section>

    <section class="">
        <header class="flex items-center gap-x-3 overflow-hidden py-4">
            <div class="grid flex-1 gap-y-1">
                <h3 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    <?php echo e(__('themes::themes.themes')); ?>

                </h3>
        
                <p class="fi-section-header-description text-sm text-gray-500 dark:text-gray-400">
                    <?php echo e(__('themes::themes.select_interface')); ?>

                </p>
            </div>
        </header>

        <div class="grid grid-cols-1 gap-6 border-t py-6">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $this->getThemes(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $name => $theme): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $noLightMode = in_array(\Hasnayeen\Themes\Contracts\HasOnlyDarkMode::class, class_implements($theme));
                    $noDarkMode = in_array(\Hasnayeen\Themes\Contracts\HasOnlyLightMode::class, class_implements($theme));
                    $supportColorChange = in_array(\Hasnayeen\Themes\Contracts\HasChangeableColor::class, class_implements($theme));
                ?>

                <?php if (isset($component)) { $__componentOriginalee08b1367eba38734199cf7829b1d1e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee08b1367eba38734199cf7829b1d1e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.section.index','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                     <?php $__env->slot('heading', null, []); ?> 
                        <div class="flex items-center space-x-4">
                            <div><?php echo e(\Illuminate\Support\Str::title($name)); ?></div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($supportColorChange): ?>
                                <span
                                    x-data="{}"
                                    x-tooltip="{
                                        content: '<?php echo e(__('themes::themes.support_changing_primary_color')); ?>',
                                        theme: $store.theme,
                                    }"
                                    class="bg-primary-200 flex items-center justify-center p-1 rounded-full">
                                    <svg class="w-4 h-4 dark:text-gray-800" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-paintbrush-2">
                                        <path d="M14 19.9V16h3a2 2 0 0 0 2-2v-2H5v2c0 1.1.9 2 2 2h3v3.9a2 2 0 1 0 4 0Z" />
                                        <path d="M6 12V2h12v10" />
                                        <path d="M14 2v4" />
                                        <path d="M10 2v2" />
                                    </svg>
                                </span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $noLightMode): ?>
                                <span
                                    x-data="{}"
                                    x-tooltip="{
                                        content: '<?php echo e(__('themes::themes.support_light_mode')); ?>',
                                        theme: $store.theme,
                                    }"
                                    class="bg-primary-200 flex items-center justify-center p-1 rounded-full">
                                    <svg class="w-4 h-4 dark:text-gray-800" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sun">
                                        <circle cx="12" cy="12" r="4" />
                                        <path d="M12 2v2" />
                                        <path d="M12 20v2" />
                                        <path d="m4.93 4.93 1.41 1.41" />
                                        <path d="m17.66 17.66 1.41 1.41" />
                                        <path d="M2 12h2" />
                                        <path d="M20 12h2" />
                                        <path d="m6.34 17.66-1.41 1.41" />
                                        <path d="m19.07 4.93-1.41 1.41" />
                                    </svg>
                                </span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $noDarkMode): ?>
                                <span
                                    x-data="{}"
                                    x-tooltip="{
                                        content: '<?php echo e(__('themes::themes.support_dark_mode')); ?>',
                                        theme: $store.theme,
                                    }"
                                    class="bg-primary-200 flex items-center justify-center p-1 rounded-full">
                                    <svg class="w-4 h-4 dark:text-gray-800" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-moon">
                                        <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z" />
                                    </svg>
                                </span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if($this->getCurrentTheme()->getName() === $name): ?>
                                <span
                                    x-data="{}"
                                    x-tooltip="{
                                        content: '<?php echo e(__('themes::themes.theme_active')); ?>',
                                        theme: $store.theme,
                                    }"
                                    class="bg-primary-200 flex items-center justify-center p-1 rounded-full">
                                    <svg class="w-4 h-4 dark:text-gray-800" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-circle-2">
                                        <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z" />
                                        <path d="m9 12 2 2 4-4" />
                                    </svg>
                                </span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                     <?php $__env->endSlot(); ?>
                    
                     <?php $__env->slot('headerEnd', null, []); ?> 
                        <?php if (isset($component)) { $__componentOriginal6330f08526bbb3ce2a0da37da512a11f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.button.index','data' => ['wire:click' => 'setTheme(\''.e($name).'\')','size' => 'xs','outlined' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:click' => 'setTheme(\''.e($name).'\')','size' => 'xs','outlined' => true]); ?>
                            <?php echo e(__('themes::themes.select')); ?>

                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6330f08526bbb3ce2a0da37da512a11f)): ?>
<?php $attributes = $__attributesOriginal6330f08526bbb3ce2a0da37da512a11f; ?>
<?php unset($__attributesOriginal6330f08526bbb3ce2a0da37da512a11f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6330f08526bbb3ce2a0da37da512a11f)): ?>
<?php $component = $__componentOriginal6330f08526bbb3ce2a0da37da512a11f; ?>
<?php unset($__componentOriginal6330f08526bbb3ce2a0da37da512a11f); ?>
<?php endif; ?>
                     <?php $__env->endSlot(); ?>

                    <?php
                        $noLightMode = in_array(\Hasnayeen\Themes\Contracts\HasOnlyDarkMode::class, class_implements($theme));
                        $noDarkMode = in_array(\Hasnayeen\Themes\Contracts\HasOnlyLightMode::class, class_implements($theme));
                    ?>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($noLightMode): ?>
                                <h3 class="text-sm font-semibold text-gray-600 pb-4"><?php echo e(__('themes::themes.no_light_mode')); ?></h3>
                            <?php else: ?>
                                <h3 class="text-sm font-semibold text-gray-600 pb-4"><?php echo e(__('themes::themes.light')); ?></h3>
                                <img src="<?php echo e(url('https://raw.githubusercontent.com/Hasnayeen/themes/3.x/assets/'.$name.'-light.png')); ?>" alt="<?php echo e($name); ?> theme preview (light version)" class="border dark:border-gray-700 rounded-lg">
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
        
                        <div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($noDarkMode): ?>
                                <h3 class="text-sm font-semibold text-gray-600 pb-4"><?php echo e(__('themes::themes.no_dark_mode')); ?></h3>
                            <?php else: ?>
                                <h3 class="text-sm font-semibold text-gray-600 pb-4"><?php echo e(__('themes::themes.dark')); ?></h3>
                                <img src="<?php echo e(url('https://raw.githubusercontent.com/Hasnayeen/themes/3.x/assets/'.$name.'-dark.png')); ?>" alt="<?php echo e($name); ?> theme preview (dark version)" class="border dark:border-gray-700 rounded-lg">
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalee08b1367eba38734199cf7829b1d1e9)): ?>
<?php $attributes = $__attributesOriginalee08b1367eba38734199cf7829b1d1e9; ?>
<?php unset($__attributesOriginalee08b1367eba38734199cf7829b1d1e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalee08b1367eba38734199cf7829b1d1e9)): ?>
<?php $component = $__componentOriginalee08b1367eba38734199cf7829b1d1e9; ?>
<?php unset($__componentOriginalee08b1367eba38734199cf7829b1d1e9); ?>
<?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </section>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $attributes = $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $component = $__componentOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?>
<?php /**PATH C:\Users\Alex LC\Documents\Perflo-Plast\backend\resources\views/vendor/themes/filament/pages/themes.blade.php ENDPATH**/ ?>