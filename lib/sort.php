<?HH
/**
 * Copyright 2015 Rick Mac Gillis
 *
 * Implements various sorting algorithms optimized for speed.
 */

namespace HackFastAlgos;

class Sort
{
	/**
	 * SelectionSort sorts a vector in Theta(n^2) time (Quadratic time). This algorithm
	 * may be faster than MergeSort or QuickSort when sorting very small vectors consisting of
	 * around less than 10 elements. If you're sorting larger arrays, consider QuickSort or MergeSort.
	 * 
	 * Learn more @link https://en.wikipedia.org/wiki/Selection_sort
	 * 
	 * @param Vector<T> $vector		The vector to sort from lowest to highest value
	 * @param Callable $callback	The callback to compare the values (@See usort())
	 * 
	 * @return Vector<T> The sorted vector
	 */
	public static function selectionSort<T>(Vector<T> $vector, Callable $callback) : Vector<T>
	{
		$vectorLen = $vector->count();
		
		// Loop through the vector
		for ($i = 0; $i < $vectorLen; $i++) {
			
			// Loop through the sub-vector to find any values less than $i, and swap them.
			for ($j = $i+1; $j < $vectorLen; $j++) {
				
				if ($callback($vector[$i], $vector[$j]) > 0) {
					
					$vector = static::swapValues($vector, $i, $j);
					
				}
				
			}
			
		}
		
		return $vector;
	}
	
	/**
	 * BubbleSort sorts a vector in quadratic time (Theta(n^2), and is less-efficient than
	 * InsertSort. It's still a well-known algorithm, though WikiPedia claims
	 * (@link https://en.wikipedia.org/wiki/Bubble_sort) that some researchers do not wish to have BubbleSort
	 * as part of the Computer Science curriculum. BubbleSort is part of this library as a benchmark.
	 * 
	 * Learn more @link https://en.wikipedia.org/wiki/Bubble_sort
	 * 
	 * @param Vector<T> $vector		The vector to sort
	 * @param Callable $callback	The callback to compare the values (@See usort())
	 * 
	 * @return Vector<T> The sorted vector
	 */
	public static function bubbleSort<T>(Vector<T> $vector, Callable $callback) : Vector<T>
	{
		$sortLen = $vector->count();
		while ($sortLen > 0) {
			
			$newLen = 0;
			
			for ($i = 1; $i < $sortLen; $i++) {
				
				if ($callback($vector[$i-1], $vector[$i]) > 0) {
					
					static::swapValues($vector, $i-1, $i);
					$newLen = $i;
					
				}
				
			}
			
			$sortLen = $newLen;
			
		}
		
		return $vector;
	}
	
	/**
	 * InsertSort sorts a vector in quadratic time (Theta(n^2)), though it improves on
	 * SelectionSort. InsertSort is only useful for very small vectors. If you have a larger
	 * vector greater than around 10 elements, then try MergeSort or QuickSort.
	 * 
	 * Learn more @link https://en.wikipedia.org/wiki/Insertion_sort
	 * 
	 * @param Vector<T> $vector		The vector to sort
	 * @param Callable $callback	The callback to compare the values (@See usort())
	 * 
	 * @return Vector<T> The sorted vector
	 */
	public static function insertSort<T>(Vector<T> $vector, Callable $callback) : Vector<T>
	{
		$vectorLen = $vector->count();
		for ($i = 1; $i < $vectorLen; $i++) {
			
			$key = $vector[$i];
			$j = $i;
			
			while ($j > 0) {
				
				if ($callback($vector[$j-1], $key) <= 0) {
					break;
				}
				
				$vector[$j] = $vector[$j-1];
				$j--;
				
			}
			
			$vector[$j] = $key;
			
		}
		
		return $vector;
	}
	
	/**
	 * MergeSort runs in Big-Theta(n log n) time and is suitable for large datasets. It does not
	 * work in place, so if memory is an issue, consider QuickSort. This implementation of MergeSort
	 * takes advantage of the asynchronous features Hack provides, such that the child-nodes
	 * of the binary tree are recursed at the same time.
	 * 
	 * Learn more @link https://en.wikipedia.org/wiki/Merge_sort
	 * 
	 * @param Vector<T> $vector			The vector to sort numerically
	 * @param Callable $callback		The callback to compare the values (@See usort())
	 * @param bool $returnWaitHandler	Set this param to true to return the wait handle for the
	 * 									full MergeSort process. If you're not running other processes
	 * 									asynchronously, then leave the value at false to return the
	 * 									sorted Vector<int>.
	 * 
	 * @return T The numerically sorted vector or the Awaitable wait handle (See $returnWaitHandler)
	 */
	public static function mergeSort<T>(Vector<T> $vector, Callable $callback, bool $returnWaitHandler = false) : T
	{
		$sorted = static::mergeSortAsync($vector, $callback);
		
		if (true === $returnWaitHandler) {
			return $sorted;
		}
		
		return $sorted->getWaitHandle()->join();
	}
	
	/**
	 * Async handler for MergeSort
	 * 
	 * @access protected
	 * @param Vector<T> $vector		The vector to sort
	 * @param Callable $callback	The callback to compare the values (@See usort())
	 * @param ?int $start			The key to start sorting at (Set by the recursion)
	 * @param ?int $end				The key to stop sorting at (Set by the recursion)
	 * 
	 * @return Awaitable<Vector<T>> The wait handler containing the sorted vector
	 */
	protected static async function mergeSortAsync<T>(Vector<T> $vector, Callable $callback, ?int $start = null, ?int $end = null) : Awaitable<Vector<T>>
	{
		$start	= (null === $start) ? 0 : $start;
		$end	= (null === $end) ? $vector->count()-1 : $end;
		
		if ($start < $end) {
			
			/*
			 * We need to wait for any children to do the splits so merge() will have everything it
			 * needs at this recursion level.
			 */
			$halves = await static::mergeSortHalves($vector, $callback, $start, $end);
			return static::merge($vector, $callback, $halves[0], $halves[1], $halves[2]);
				
		}
		
		return $vector;
	}
	
	/**
	 * Run the binary split asynchronously (awaited in mergeSortAsync)
	 * 
	 * @access protected
	 * @param Vector<T> $vector		The vector to sort
	 * @param Callable $callback	The callback to compare the values (@See usort())
	 * @param int $start			The key for the start of the parent sub-vector
	 * @param int $end				The key for the end of the parent sub-vector
	 * 
	 * @return Awaitable<Vector<int>> The vector containing the positions of start, mid, end for the child sub-vectors
	 */
	protected static async function mergeSortHalves<T>(Vector<T> $vector, Callable $callback, int $start, int $end) : Awaitable<Vector<int>>
	{
		$mid = (int) floor(($start+$end)/2);
		static::mergeSortAsync($vector, $callback, $start, $mid);
		static::mergeSortAsync($vector, $callback, $mid+1, $end);
		
		return Vector{$start, $mid, $end};
	}
	
	/**
	 * Perform the merge portion of MergeSort
	 * 
	 * @access protected
	 * @param Vector<T> $vector		The vector to sort
	 * @param Callable $callback	The callback to compare the values (@See usort())
	 * @param int $start			The key for the beginning of the sub-vectors
	 * @param int $mid				The key right smack dab in the middle of the sub-vectors
	 * @param int $end				The key at the end of the sub-vectors
	 * 
	 * @return Vector<T> The merged and sorted vector
	 */
	protected static function merge<T>(Vector<T> $vector, Callable $callback, int $start, int $mid, int $end) : Vector<T>
	{
		$left	= Vector{};
		$right	= Vector{};
		
		// Create temporary arrays (This is why MergeSort does not work in-place.
		for ($k = $start; $k <= $mid; $k++) {
			
			$left[] = $vector[$k];
			
		}
		
		for ($k = $mid+1; $k <= $end; $k++) {
			
			$right[] = $vector[$k];
			
		}
		
		$i = $j = 0;
		$k = $start;
		
		// Sort the vector by combining both sub-vectors
		while ($i < $left->count() && $j < $right->count()) {
			
			if ($callback($right[$j], $left[$i]) > 0) {
				
				$vector[$k] = $left[$i];
				$i++;
				
			} else {
				
				$vector[$k] = $right[$j];
				$j++;
				
			}
			
			$k++;
			
		}
		
		// Only keys on $left remain, so add those now.
		while ($i < $left->count()) {
			
			$vector[$k] = $left[$i];
			$i++;
			$k++;
			
		}
		
		// Only keys on $right remain, so add those now.
		while ($j < $right->count()) {
			
			$vector[$k] = $right[$j];
			$j++;
			$k++;
			
		}
		
		return $vector;
	}
	
	/**
	 * Quick Sort works in big-O(n log n) time on average, though in the worst case, it
	 * will operate in big-Omega(n^2) time. This method aims for a 3 to 1 split by taking
	 * the median value of a random sampling of values from $array. As the sampling period
	 * takes extra work, set the $minArraySize to the smallest array to take a sampling from.
	 * Anything less than the specified array width will not using sampling.
	 * 
	 * This algorithm is designed to improve on the performance of PHP's sort(), such that it
	 * gives you better speed control through the use of random sampling.
	 * 
	 * NOTE: Leave $numRandom at -1 to allow the algorithm to determine the optimum number of
	 * values to sample based on the width of the array at the current recursion level.
	 * 
	 * @param array $vector			The Vector to sort
	 * @param integer $pivot		The index to use as the pivot (Or leave at null)
	 * @param integer $numRandom	The number of random elements to sample for each iteration (See above notes)
	 * @param integer $minArraySize	The minimum array width to pull samples from
	 * 
	 * @return Vector<int> The sorted integer vector
	 */
	public static function quickSort(Vector<int> $vector, ?int $pivot = null, int $numRandom = -1, int $minArraySize = null) : Vector<int>
	{
		// https://en.wikipedia.org/wiki/Quicksort
	}
	
	public static function intelligentSort(Vector<int> $vector) : Vector<int>
	{
		// Guess at which sorting algorithm to use.
	}
	
	public static function heapSort(Vector<int> $vector) : Vector<int>
	{
		// https://en.wikipedia.org/wiki/Heapsort
	}
	
	public static function bucketSort(Vector<int> $vector, int $buckets) : Vector<int>
	{
		// https://en.wikipedia.org/wiki/Bucket_sort
	}
	
	public static function countingSort(Vector<int> $vector, int $maxValue) : Vector<int>
	{
		// https://en.wikipedia.org/wiki/Counting_sort
	}
	
	public static function radixSort(Vector<int> $vector)
	{
		// https://en.wikipedia.org/wiki/Radix_sort
		// https://www.codingbot.net/2013/02/radix-sort-algorithm-and-c-code.html
	}
	
	/**
	 * Quickly swap array values
	 * 
	 * @access protected
	 * @param Vector<int> $vector	The vector on which to swap the keys
	 * @param int $indexA			The first index to swap
	 * @param int $indexB			The second index to swap
	 * 
	 * @return Vector<int> The integer vector object with the elements swapped
	 */
	protected static function swapValues(Vector<int> $vector, int $indexA, int $indexB) : Vector<int>
	{
		$oldA = $vector[$indexA];
		$vector[$indexA] = $vector[$indexB];
		$vector[$indexB] = $oldA;

		return $vector;
	}
}