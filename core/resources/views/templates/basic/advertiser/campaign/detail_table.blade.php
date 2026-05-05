      @php
          use Carbon\Carbon;
      @endphp


      <table class="table--responsive--lg table">
          <thead>
              <tr>
                  <th>@lang('Advertisement Name | Ad Running')</th>
                  <th>@lang('Start Date - End Date')</th>
                  <th>@lang('Ad Schedule Type')</th>
                  <th>@lang('Ad Reached')</th>
                  <th>@lang('Ad Engagement')</th>
                  <th>@lang('Status')</th>
                  <th>@lang('Ad Type')</th>
                  <th>@lang('Daily Budget')</th>
                  <th>@lang('Ad Cost')</th>
                  <th>@lang('Action')</th>
              </tr>
          </thead>
          <tbody>
              @forelse ($campaign->advertisements as $advertisement)
                  @php
                      $totalAdDays = 0;

                      if ($advertisement->schedule_type == 1) {
                          $start = Carbon::parse($advertisement->start_date);
                          $end = Carbon::parse($advertisement->end_date);

                          $totalAdDays += $start->diffInDays($end) + 1;
                      }

                      if ($advertisement->schedule_type == 2 && $advertisement->schedules) {
                          foreach ($advertisement->schedules as $schedule) {
                              $start = Carbon::parse($schedule->custom_start_date);
                              $end = Carbon::parse($schedule->custom_end_date);

                              $totalAdDays += $start->diffInDays($end) + 1;
                          }
                      }

                  @endphp

                  <tr>
                      <td>{{ __($advertisement->title) }} <br>
                          {{ round($totalAdDays) }} @lang('Days')

                      </td>

                      <td>{{ showDateTime($advertisement->start_date, 'Y-m-d') }} -
                          {{ showDateTime($advertisement->end_date, 'Y-m-d') }}</td>

                      <td>
                          @if ($advertisement->schedule_type == 1)
                              @lang('Daily')
                          @elseif ($advertisement->schedule_type == 2)
                              @lang('Custom')
                          @endif
                      </td>

                      <td>
                          {{ formatNumber($advertisement->ad_reached) }} <br>
                          <small class="text--success">@lang('Ads Reached'):
                              {{ formatNumber($advertisement->adReaches()->count()) }} </small>
                      </td>
                      <td>
                          {{ formatNumber($advertisement->ad_engagement) }} <br>
                          <small class="text--success">@lang('Ads Engagement'):
                              {{ formatNumber($advertisement->advertisementAnalytics()->count()) }} </small>

                      </td>



                      <td> @php
                          echo $advertisement->statusBadge;
                      @endphp</td>
                      <td>
                          @php
                              echo $advertisement->adTypeBadge;
                          @endphp
                      </td>
                      <td>{{ showAmount($advertisement->daily_costs) }}</td>
                      <td>

                          <span>
                              <small>
                                  {{ round($totalAdDays) }} x {{ showAmount($advertisement->daily_costs) }} =
                              </small>
                          </span> <br>
                          {{ showAmount($advertisement->total_amount) }}

                      </td>
                      <td>
                   <div class="action-buttons">

                            @if ($advertisement->status == Status::RUNNING)
                                <button class="action-btn  pause-btn confirmationBtn"
                                    data-action="{{ route('user.advertiser.status', $advertisement->id) }}"
                                    data-question="@lang('Are you sure want to pause this advertisement?')">
                                    <i class="las la-pause"></i>@lang('Pause')
                                </button>
                            @elseif($advertisement->status == Status::PAUSE)
                                <button class="action-btn info-btn confirmationBtn"
                                    data-action="{{ route('user.advertiser.status', $advertisement->id) }}"
                                    data-question="@lang('Are you sure want to pause this advertisement?')">
                                    <i class="las la-pause"></i>@lang('Running')
                                </button>
                            @endif
  
                          <a href="{{ route('user.advertiser.ad.edit', $advertisement->id) }}" class="action-btn  info-btn">@lang('Edit')</a>
                        
                             
                        </div>
                           
                      </td>
                  </tr>
              @empty
                  <tr>
                      <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                  </tr>
              @endforelse
          </tbody>
      </table>
      <x-confirmation-modal frontend="true" />
